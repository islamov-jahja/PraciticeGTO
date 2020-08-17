<?php

namespace App\Persistance\Repositories\TeamLead;
use App\Domain\Models\EventParticipant\EventParticipant;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\TeamLead\TeamLead;
use App\Domain\Models\User\UserCreater;
use App\Persistance\ModelsEloquant\Team\Team;
use App\Persistance\ModelsEloquant\TeamLead\TeamLead as TeamLeadPDO;
use DateTime;

class TeamLeadRepository implements IRepository
{
    private $dateForTeamLead = [
        'user.password',
        'user.user_id',
        'user.name',
        'user.email',
        'user.role_id',
        'user.is_activity',
        'user.registration_date',
        'team_lead.team_lead_id',
        'team_lead.team_id',
        'user.gender',
        'user.date_of_birth'];

    public function get(int $id): ?IModel
    {
        $result = TeamLeadPDO::query()
            ->join('user', 'team_lead.user_id', '=', 'user.user_id')
            ->where('team_lead.team_lead_id', '=', $id)
            ->get($this->dateForTeamLead);

        if (count($result) == 0){
            return null;
        }

        return $this->getTeamLead($result[0]);
    }

    /**
     * @inheritDoc
     */
    public function getAll(): ?array
    {
        $results = TeamLeadPDO::query()
            ->join('user', 'team_lead.user_id', '=', 'user.user_id')
            ->get($this->dateForTeamLead);

        $teamLeads = [];
        foreach ($results as $result){
            $teamLeads[] = $this->getTeamLead($result);
        }

        return $teamLeads;
    }

    /**@var $model TeamLead*/
    public function add(IModel $model): int
    {
        $result = TeamLeadPDO::query()
            ->create([
                'team_id' => $model->getTeamId(),
                'user_id' => $model->getUserId()
            ]);

        return $result->getAttribute('team_lead_id');
    }

    public function delete(int $id)
    {
        TeamLeadPDO::query()
            ->where('team_lead_id', '=', $id)
            ->delete();
    }

    public function update(IModel $model)
    {
        // TODO: Implement update() method.
    }

    private function getTeamLead($params):TeamLead
    {
        $user = UserCreater::createModel([
            'id' => $params['user_id'],
            'name' => $params['name'],
            'password' => $params['password'],
            'email' => $params['email'],
            'roleId' => $params['role_id'],
            'dateTime' => new DateTime($params['registration_date']),
            'isActivity' => $params['is_activity'],
            'dateOfBirth' => new DateTime($params['date_of_birth']),
            'gender' => $params['gender']
        ]);

        return new TeamLead
        (
            $params['team_lead_id'],
            $params['team_id'],
            $params['user_id'],
            $user,
        );
    }

    public function getByEmailAndTeamId(string $email, int $teamId)
    {
        $result = TeamLeadPDO::query()
            ->join('user', 'team_lead.user_id', '=', 'user.user_id')
            ->where([
                'user.email' => $email,
                'team_lead.team_id' => $teamId
            ])
            ->get($this->dateForTeamLead);

        if (count($result) == 0){
            return null;
        }

        return $this->getTeamLead($result[0]);
    }

    /**@return TeamLead[]*/
    public function getByTeamId(int $teamId):array
    {
        $results = TeamLeadPDO::query()
            ->join('user', 'team_lead.user_id', '=', 'user.user_id')
            ->where([
                'team_lead.team_id' => $teamId
            ])
            ->get($this->dateForTeamLead);

        $teamLeads = [];
        foreach ($results as $result){
            $teamLeads[] = $this->getTeamLead($result);
        }

        return $teamLeads;
    }
}