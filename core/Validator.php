<?php
namespace Core;
use App\Services\Response;
class Validator
{
    private $errors = [];
    private function sanitize($value)
    {
        if (!is_null($value)) {
            return htmlspecialchars(strip_tags(trim($value)));
        }
    }

    public function validate($data, $rules, $id = null)
    {
        if (empty($data)) {
            Response::jsonResponse([
                "status" => HTTP_UNPROCESSABLE_ENTITY,
                "message" => trans('empty_request_body')
            ]);
            return;
        }
        foreach ($rules as $field => $ruleSet) {
            $rulesArray = explode('|', $ruleSet);
            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $data[$field] ?? null, $rule, $data, $id);
            }
        }
        if ($this->errors) {
            Response::jsonResponse(["status" => HTTP_UNPROCESSABLE_ENTITY, "data" => $this->errors]);
        } else {
            return $data;
        }
    }

    private function applyRule($field, $value, $rule, $data, $id = null)
    {
        $value = $this->sanitize($value);
        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    $this->errors[$field][] = $field . ' ' . trans('required_validation');
                }
                break;
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = $field . ' ' . trans('valid_email');
                }
                break;
            case 'password':
                if (is_null($value) || strlen((string) $value) < 8) {
                    $this->errors[$field][] = $field . ' ' . trans('passsword_length');
                }
            case 'confirm_password':
                if ($value !== ($data['password'] ?? null)) {
                    $this->errors[$field][] = trans('confirm_password');
                }
                break;
            case 'integer':
                if (!filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->errors[$field][] = $field . ' ' . trans('must_be_integer');
                }
                break;
            default:
                if (strpos($rule, 'unique:') === 0) {
                    $table = str_replace('unique:', '', $rule); // Extract the table name
                    if ($this->isExists($table, $field, $value, $id)) {
                        $this->errors[$field][] = $field . ' ' . trans('unique_field');
                    }
                    break;
                }
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function hasErrors()
    {
        return !empty($this->errors);
    }

    private function isExists($table, $field, $value, $id = null)
    {
        $db = new Database();
        $query = "SELECT COUNT(*) FROM $table WHERE $field = :value";
        if ($id !== null) {
            $query .= " AND id != :id";
        }
        $stmt = $db->prepare($query);
        $stmt->bindValue(':value', $value);

        if ($id !== null) {
            $stmt->bindValue(':id', $id);
        }

        $stmt->execute();
        // Get the count of matching records
        $count = $stmt->fetchColumn();
        return $count > 0; // Return true if the value exists, false otherwise
    }
}
