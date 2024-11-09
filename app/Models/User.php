<?php

namespace App\Models;

use Core\Model;
use App\Services\Response;
use App\Services\JwtService;

class User extends Model
{
    private $table_name = "users";

    /**
     * Changes the role of a user in the database.
     *
     * @param int|string $id The ID of the user whose role is to be changed.
     * @param string $newRole The new role to assign to the user.
     * 
     * @return array An array containing the status and message of the operation.
     */
    public function changeRole($id, $newRole)
    {
        $this->findRole($newRole);
        $this->findUser($id);
        $result = $this->QueryBuilder->updateFields($this->table_name, ['role' => $newRole], ['id' => $id]);
        if ($result) {
            $response = ["status" => HTTP_OK, "message" => trans('role_updated')];
        } else {
            $response = ["status" => HTTP_INTERNAL_SERVER_ERROR, "message" => trans('server_error')];
        }
        return $response;
    }

    /**
     * Validates the provided role against a list of allowed roles.
     *
     * @param string $role The role to validate.
     * 
     * @return void
     *
     * @throws \Exception If the role is invalid, a JSON response with an error message is returned.
     */
    public  function findRole($role)
    {
        if (!in_array($role, ['admin', 'operator'])) {
            Response::jsonResponse(["status" => HTTP_BAD_REQUEST, "message" => trans('invalid_role')]);
        }
    }
    /**
     * Retrieves a user by their ID and validates their existence.
     *
     * @param int|string $id The ID of the user to find.
     * 
     * @return array|null Returns the user data if found.
     *
     * @throws \Exception If the user does not exist, a JSON response with an error message is returned.
     */
    public function findUser($id)
    {
        $user = $this->QueryBuilder->find($this->table_name, ['id' => $id], ['password', 'token_version']);
        if (!$user) {
            Response::jsonResponse(["status" => HTTP_BAD_REQUEST, "message" => trans('user_not_exist')]);
        }
        return $user;
    }

    public function updateInfo($userID, $data)
    {
        $fields = [
            'full_name' => $data['full_name'],
            'email' => $data['email']
        ];
        $conditions = [
            'id' => $userID
        ];
        $result = $this->QueryBuilder->updateFields($this->table_name, $fields, $conditions);
        if ($result) {
            $response = ["status" => HTTP_OK, "message" => trans('profile_updated')];
        } else {
            $response = ["status" => HTTP_INTERNAL_SERVER_ERROR, "message" => trans('server_error')];
        }
        return $response;
    }
    public function resetPassword($userID, $newPass)
    {
        $fields = [
            'password' => password_hash($newPass, PASSWORD_BCRYPT)
        ];
        $conditions = [
            'id' => $userID
        ];
        $result = $this->QueryBuilder->updateFields($this->table_name, $fields, $conditions);
        if (!$result) {
            $response = Response::jsonResponse(["status" => HTTP_INTERNAL_SERVER_ERROR, "message" => trans('server_error')]);
        } else {
            $response = Response::jsonResponse(["status" => HTTP_OK, "message" => trans('password_changed')]);
        }
        return $response;
    }

    /**
     * Changes the approval status of a user in the database.
     *
     * @param int|string $userID The ID of the user whose status is to be changed.
     * @param mixed $status The new status to set for the user (e.g., approved or disabled).
     * 
     * @return bool Returns true if the status was successfully updated, false otherwise.
     */

    public function changeStatus($userID, $status)
    {
        $this->findUser($userID);
        return $this->QueryBuilder->updateFields($this->table_name, ['approved' => $status], ['id' => $userID]);
    }

    /**
     * Retrieves a paginated list of all users from the database.
     *
     * @param int $page The page number to retrieve.
     * 
     * @return array The paginated list of users.
     */
    public function getAllUsers($page)
    {
        $columns = 'id, full_name, email, role,approved';
        return $this->QueryBuilder->paginate($this->table_name, $page, config('PAGINATE_NUM'), $columns);
    }


    /**
     * Updates the token version for a specific user in the database.
     *
     * @param int|string $userId The ID of the user whose token version is to be updated.
     * @param int|string|null $newVersion The new token version to set (optional).
     * 
     * @return void
     */
    public function updateTokenVersion($userId, $newVersion = null)
    {
        $fields = [
            'token_version' => $newVersion,
        ];
        $conditions = [
            'id' => $userId
        ];
        $this->QueryBuilder->updateFields($this->table_name, $fields, $conditions);
    }

    /**
     * Authenticates a user by their email and password.
     *
     * @param array $data The login credentials containing:
     * - 'email': The user's email address.
     * - 'password': The user's password.
     *
     * @return array An associative array containing:
     * - 'status': HTTP status code.
     * - 'message': A descriptive message about the login result.
     * - 'data': Optional. An associative array containing the generated token if login is successful.
     */
    public function Login(array $data)
    {
        $user = $this->getUser($data['email']);
        if (!$user) {
            return [
                "status" => HTTP_UNAUTHORIZED,
                "message" => trans('Email_not_found')
            ];
        }
        $result = $this->checkPassword($user['password'], $data['password']);
        if (!$result) {
            return [
                "status" => HTTP_UNAUTHORIZED,
                "message" => trans('wrong_password')
            ];
        }
        $active = $this->checkIsActivated($user);
        if (!$active) {
            return ["status" => HTTP_FORBIDDEN, "message" => trans('user_not_activated')];
        }

        $token = $this->generateToken($user);
        return [
            "status" => HTTP_OK,
            "message" => trans('login_successful'),
            "data" => ['token' => $token]
        ];
    }
    /**
     * Retrieves a user record from the database by email.
     * @param string $email The email address of the user to retrieve.
     * @return array|null An associative array containing the user's information if found, 
     * or null if no user with the specified email exists.
     */
    private function getUser($email)
    {
        $user = $this->QueryBuilder->find($this->table_name, ['email' => $email]);
        return $user;
    }

    /**
     * Checks if the user's account is activated based on the 'approved' status.
     * @param array $user An associative array containing user information, 
     *  which must include the 'approved' key.
     *
     * @return bool Returns true if the user's account is activated (approved), 
     * or false if it is not activated.
     */
    private function checkIsActivated($user)
    {
        return $user['approved'] == config('USER_STATUS_APPROVED');
    }

    /**
     * Generates a JWT  for  user.
     *
     * @param array $user An associative array containing user information:
     * - 'id': The unique identifier of the user.
     * - 'email': The user's email address.
     *  - 'role': The user's role in the application.
     * @return string The generated JWT token for the user.
     */

    private function generateToken($user)
    {
        return JwtService::generateToken([
            "id" => $user['id'],
            "email" => $user['email'],
            "role" => $user['role']
        ]);
    }

    /**
     * Checks if the provided password matches the hashed password.
     *
     * @param string $password The hashed password stored in the database.
     * @param string $inputPassword The password input to be verified.
     * 
     * @return bool Returns true if the passwords match, false otherwise.
     */
    public function checkPassword($password, $inputPassword)
    {
        return password_verify($inputPassword, $password);
    }
    /**
     * Creates a new user record in the database and returns the status response.
     *
     * @param array $data User data including 'full_name', 'email', and 'password'.
     * 
     * @return array Response indicating the status of the user creation:
     */
    public function create(array $data)
    {
        $result = $this->QueryBuilder->insert($this->table_name, [
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);
        if ($result) {
            $response = ["status" => HTTP_OK, "message" => trans('user_registration_successful')];
        } else {
            $response = ["status" => HTTP_INTERNAL_SERVER_ERROR, "message" => trans('server_error')];
        }
        return $response;
    }
}
