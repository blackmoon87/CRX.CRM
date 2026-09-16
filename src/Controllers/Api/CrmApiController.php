<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Task;
use Spartan\Controller;

class CrmApiController extends Controller
{
    private function getWorkspaceId(): int
    {
        return (int)($this->session->get('api_workspace_id') 
            ?? $this->session->get('active_workspace_id') 
            ?? 1);
    }

    public function summary(): void
    {
        $wsId = $this->getWorkspaceId();
        $companies = (new Company)->table()->where('workspace_id', $wsId)->count();
        $people = (new Person)->table()->where('workspace_id', $wsId)->count();
        $deals = (new Opportunity)->table()->where('workspace_id', $wsId)->count();
        $tasks = (new Task)->table()->where('workspace_id', $wsId)->where('status', 'pending')->count();

        $this->response->json([
            'workspace_id' => $wsId,
            'companies'    => $companies,
            'people'       => $people,
            'deals'        => $deals,
            'open_tasks'   => $tasks,
        ]);
    }

    public function companies(): void
    {
        $wsId = $this->getWorkspaceId();
        $records = (new Company)->table()->where('workspace_id', $wsId)->limit(100)->get();
        $this->response->json(['data' => $records]);
    }

    public function people(): void
    {
        $wsId = $this->getWorkspaceId();
        $records = (new Person)->table()->where('workspace_id', $wsId)->limit(100)->get();
        $this->response->json(['data' => $records]);
    }

    public function opportunities(): void
    {
        $wsId = $this->getWorkspaceId();
        $records = (new Opportunity)->table()->where('workspace_id', $wsId)->limit(100)->get();
        $this->response->json(['data' => $records]);
    }

    public function tasks(): void
    {
        $wsId = $this->getWorkspaceId();
        $records = (new Task)->table()->where('workspace_id', $wsId)->limit(100)->get();
        $this->response->json(['data' => $records]);
    }
}
