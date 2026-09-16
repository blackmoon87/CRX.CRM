<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Person;
use App\Models\Task;
use App\Services\WorkspaceService;
use Spartan\Controller;

class RecycleBinController extends Controller
{
    private function getContext(): array
    {
        $userId = (int)$this->auth->id();
        $wsService = new WorkspaceService($this->session);
        return [
            'userId'      => $userId,
            'workspaceId' => $wsService->getActiveWorkspaceId($userId),
            'workspace'   => $wsService->getActiveWorkspace($userId),
            'workspaces'  => $wsService->getUserWorkspaces($userId),
        ];
    }

    public function index(): string
    {
        $ctx = $this->getContext();
        $wsId = $ctx['workspaceId'];

        $deletedCompanies = (new Company)->table()
            ->where('workspace_id', $wsId)
            ->whereNotNull('deleted_at')
            ->orderBy('deleted_at', 'DESC')
            ->get();

        $deletedPeople = (new Person)->table()
            ->where('workspace_id', $wsId)
            ->whereNotNull('deleted_at')
            ->orderBy('deleted_at', 'DESC')
            ->get();

        $deletedOpps = (new Opportunity)->table()
            ->where('workspace_id', $wsId)
            ->whereNotNull('deleted_at')
            ->orderBy('deleted_at', 'DESC')
            ->get();

        $deletedTasks = (new Task)->table()
            ->where('workspace_id', $wsId)
            ->whereNotNull('deleted_at')
            ->orderBy('deleted_at', 'DESC')
            ->get();

        $totalCount = count($deletedCompanies) + count($deletedPeople) + count($deletedOpps) + count($deletedTasks);

        return $this->render('settings/recycle_bin', [
            'title'            => 'Recycle Bin & Data Recovery',
            'companies'        => $deletedCompanies,
            'people'           => $deletedPeople,
            'opportunities'    => $deletedOpps,
            'tasks'            => $deletedTasks,
            'totalCount'       => $totalCount,
            'workspace'        => $ctx['workspace'],
            'workspaces'       => $ctx['workspaces'],
        ]);
    }

    public function restore(): void
    {
        $ctx = $this->getContext();
        $wsId = $ctx['workspaceId'];
        $type = (string)$this->request->post('entity_type');
        $id = (int)$this->request->post('id');

        $model = match($type) {
            'companies'     => new Company(),
            'people'        => new Person(),
            'opportunities' => new Opportunity(),
            'tasks'         => new Task(),
            default         => null,
        };

        if (!$model || $id <= 0) {
            $this->session->setFlash('error', 'Invalid entity to restore.');
            $this->redirect('/settings/recycle-bin');
            return;
        }

        $model->table()
            ->where('id', $id)
            ->where('workspace_id', $wsId)
            ->update(['deleted_at' => null]);

        try {
            (new Activity)->table()->insert([
                'workspace_id' => $wsId,
                'user_id'      => $ctx['userId'],
                'action'       => 'restored',
                'entity_type'  => $type,
                'entity_id'    => $id,
                'description'  => "Restored {$type} #{$id} from Recycle Bin",
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {}

        $this->session->setFlash('success', "Record #{$id} restored successfully!");
        $this->redirect('/settings/recycle-bin');
    }

    public function purge(): void
    {
        $ctx = $this->getContext();
        $wsId = $ctx['workspaceId'];
        $type = (string)$this->request->post('entity_type');
        $id = (int)$this->request->post('id');

        $model = match($type) {
            'companies'     => new Company(),
            'people'        => new Person(),
            'opportunities' => new Opportunity(),
            'tasks'         => new Task(),
            default         => null,
        };

        if (!$model || $id <= 0) {
            $this->session->setFlash('error', 'Invalid entity to purge.');
            $this->redirect('/settings/recycle-bin');
            return;
        }

        $model->table()
            ->where('id', $id)
            ->where('workspace_id', $wsId)
            ->delete();

        $this->session->setFlash('success', "Record #{$id} permanently deleted.");
        $this->redirect('/settings/recycle-bin');
    }
}
