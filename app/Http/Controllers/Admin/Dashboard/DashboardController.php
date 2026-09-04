<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Common\AccessService;
use Illuminate\Http\RedirectResponse;

/**
 * The "Dashboard" sidebar link and the post-login landing page. Per the scope
 * document, admins land on the Smart Links overview and salespeople land on
 * their own prospect list - there is no separate dashboard view to maintain.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly AccessService $access)
    {
    }

    public function index(): RedirectResponse
    {
        return $this->access->isSalesperson()
            ? redirect()->route('admin.prospects.index')
            : redirect()->route('admin.smart.dashboard');
    }
}
