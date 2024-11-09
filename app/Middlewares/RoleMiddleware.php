<?php
namespace App\Middlewares;
use App\Services\Response;
class RoleMiddleware
{
    private $requiredRoles = [];

    public function setRoles(array $roles): void
    {
        $this->requiredRoles = $roles;
    }

    public function handle()
    {
        $userRole = auth('role');
        if (empty($this->requiredRoles)) {
            return true;
        }
        if (!in_array($userRole, $this->requiredRoles)) {
            Response::jsonResponse(["status" => HTTP_FORBIDDEN, "message" => trans('Insufficient_permissions')]);
        }
        return true;
    }

}
