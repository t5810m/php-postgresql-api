<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Test route
$routes->get('test', static function () {
    return 'Test route works!';
});

/**
 * ========================================
 * API V1 Routes - HelpDesk System
 * ========================================
 *
 * Pattern: /api/v1/{resource}
 * All resource routes follow RESTful conventions:
 * - GET    /resource         -> index()   (list all)
 * - POST   /resource         -> create()  (create new)
 * - GET    /resource/{id}    -> show()    (get one)
 * - PUT    /resource/{id}    -> update()  (update)
 * - DELETE /resource/{id}    -> delete()  (delete)
 */

// ==================== Public: Auth ====================
$routes->group('api/v1/auth', static function ($routes) {
    $routes->post('login', 'AuthController::login');
    $routes->post('refresh', 'AuthController::refresh');
});

// ==================== Protected: All API resources ====================
$routes->group('api/v1', ['filter' => 'jwt'], static function ($routes) {

    // ==================== Users ====================
    $routes->get('users', 'UsersController::index');
    $routes->post('users', 'UsersController::create');
    $routes->get('users/(:any)', 'UsersController::show/$1');
    $routes->put('users/(:any)', 'UsersController::update/$1');
    $routes->delete('users/(:any)', 'UsersController::delete/$1');

    // ==================== Tickets ====================
    $routes->get('tickets', 'TicketsController::index');
    $routes->post('tickets', 'TicketsController::create');
    $routes->get('tickets/(:any)', 'TicketsController::show/$1');
    $routes->put('tickets/(:any)', 'TicketsController::update/$1');
    $routes->delete('tickets/(:any)', 'TicketsController::delete/$1');

    // ==================== Departments ====================
    $routes->get('departments', 'DepartmentController::index');
    $routes->post('departments', 'DepartmentController::create');
    $routes->get('departments/(:any)', 'DepartmentController::show/$1');
    $routes->put('departments/(:any)', 'DepartmentController::update/$1');
    $routes->delete('departments/(:any)', 'DepartmentController::delete/$1');

    // ==================== Locations ====================
    $routes->get('locations', 'LocationController::index');
    $routes->post('locations', 'LocationController::create');
    $routes->get('locations/(:any)', 'LocationController::show/$1');
    $routes->put('locations/(:any)', 'LocationController::update/$1');
    $routes->delete('locations/(:any)', 'LocationController::delete/$1');

    // ==================== Roles ====================
    $routes->get('roles', 'RoleController::index');
    $routes->post('roles', 'RoleController::create');
    $routes->get('roles/(:any)', 'RoleController::show/$1');
    $routes->put('roles/(:any)', 'RoleController::update/$1');
    $routes->delete('roles/(:any)', 'RoleController::delete/$1');

    // ==================== Permissions ====================
    $routes->get('permissions', 'PermissionController::index');
    $routes->post('permissions', 'PermissionController::create');
    $routes->get('permissions/(:any)', 'PermissionController::show/$1');
    $routes->put('permissions/(:any)', 'PermissionController::update/$1');
    $routes->delete('permissions/(:any)', 'PermissionController::delete/$1');

    // ==================== Role Permissions ====================
    $routes->get('role-permissions', 'RolePermissionController::index');
    $routes->post('role-permissions', 'RolePermissionController::create');
    $routes->get('role-permissions/(:any)', 'RolePermissionController::show/$1');
    $routes->put('role-permissions/(:any)', 'RolePermissionController::update/$1');
    $routes->delete('role-permissions/(:any)', 'RolePermissionController::delete/$1');

    // ==================== User Roles ====================
    $routes->get('user-roles', 'UserRoleController::index');
    $routes->post('user-roles', 'UserRoleController::create');
    $routes->get('user-roles/(:any)', 'UserRoleController::show/$1');
    $routes->put('user-roles/(:any)', 'UserRoleController::update/$1');
    $routes->delete('user-roles/(:any)', 'UserRoleController::delete/$1');

    // ==================== Ticket Categories ====================
    $routes->get('ticket-categories', 'TicketCategoryController::index');
    $routes->post('ticket-categories', 'TicketCategoryController::create');
    $routes->get('ticket-categories/(:any)', 'TicketCategoryController::show/$1');
    $routes->put('ticket-categories/(:any)', 'TicketCategoryController::update/$1');
    $routes->delete('ticket-categories/(:any)', 'TicketCategoryController::delete/$1');

    // ==================== Ticket Priorities ====================
    $routes->get('ticket-priorities', 'TicketPriorityController::index');
    $routes->post('ticket-priorities', 'TicketPriorityController::create');
    $routes->get('ticket-priorities/(:any)', 'TicketPriorityController::show/$1');
    $routes->put('ticket-priorities/(:any)', 'TicketPriorityController::update/$1');
    $routes->delete('ticket-priorities/(:any)', 'TicketPriorityController::delete/$1');

    // ==================== Ticket Statuses ====================
    $routes->get('ticket-statuses', 'TicketStatusController::index');
    $routes->post('ticket-statuses', 'TicketStatusController::create');
    $routes->get('ticket-statuses/(:any)', 'TicketStatusController::show/$1');
    $routes->put('ticket-statuses/(:any)', 'TicketStatusController::update/$1');
    $routes->delete('ticket-statuses/(:any)', 'TicketStatusController::delete/$1');

    // ==================== Ticket Assignments ====================
    $routes->get('ticket-assignments', 'TicketAssignmentController::index');
    $routes->post('ticket-assignments', 'TicketAssignmentController::create');
    $routes->get('ticket-assignments/(:any)', 'TicketAssignmentController::show/$1');
    $routes->put('ticket-assignments/(:any)', 'TicketAssignmentController::update/$1');
    $routes->delete('ticket-assignments/(:any)', 'TicketAssignmentController::delete/$1');

    // ==================== Ticket Comments ====================
    $routes->get('ticket-comments', 'TicketCommentController::index');
    $routes->post('ticket-comments', 'TicketCommentController::create');
    $routes->get('ticket-comments/(:any)', 'TicketCommentController::show/$1');
    $routes->put('ticket-comments/(:any)', 'TicketCommentController::update/$1');
    $routes->delete('ticket-comments/(:any)', 'TicketCommentController::delete/$1');

    // ==================== Ticket Attachments ====================
    $routes->get('ticket-attachments', 'TicketAttachmentController::index');
    $routes->post('ticket-attachments', 'TicketAttachmentController::create');
    $routes->get('ticket-attachments/(:any)', 'TicketAttachmentController::show/$1');
    $routes->put('ticket-attachments/(:any)', 'TicketAttachmentController::update/$1');
    $routes->delete('ticket-attachments/(:any)', 'TicketAttachmentController::delete/$1');

    // ==================== Ticket History (read-only audit log) ====================
    $routes->get('ticket-history', 'TicketHistoryController::index');
    $routes->get('ticket-history/(:any)', 'TicketHistoryController::show/$1');

});

// Swagger UI and OpenAPI spec - public, no auth required
$routes->get('api/docs', 'SwaggerController::ui');
$routes->get('api/v1/docs', 'SwaggerController::docs');
