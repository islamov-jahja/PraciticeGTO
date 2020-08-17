<?php

namespace App\Persistance\Repositories\Team;
use App\Domain\Models\Event\Event as EventModel;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\Team\Team;
use App\Persistance\ModelsEloquant\Event\Event;
use App\Persistance\ModelsEloquant\EventParticipant\EventParticipant;
use App\Persistance\ModelsEloquant\Team\Team as TeamPdo;
use App\Persistance\ModelsEloquant\TeamLead\TeamLead;
use App\Persistance\ModelsEloquant\Secretary\SecretaryOnOrganization as SecretaryOnOrgPDO;
use App\Persistance\ModelsEloquant\Event\Event as EventPDO;

class TeamRepository implements IRepository
{
    public function get(int $id): ?IModel
    {
        $results = TeamPdo::query()->where('team_id', '=', $id)->get();
        if (count($results) == 0){
            return null;
        }
        $teams = $this->getTeams($results);
        if ($teams != null){
            return $teams[0];
        }

        return null;
    }

    /**
     * @inheritDoc
     */

    public function getAllForEvent(int $eventId):?array
    {
        $results = TeamPdo::query()->where('event_id', '=', $eventId)->get();
        if (count($results) == 0){
            return null;
        }

        return $this->getTeams($results);
    }

    /**
     * @param int $eventId
     * @param int $organizationId
     * @return Team[]
     */
    public function getAllFilteredByEventIdOrgId(int $organizationId, int $eventId):array
    {
        $objects = TeamPdo::query()
            ->join('event', 'event.event_id', '=', 'team.event_id')
            ->where([
                'event.organization_id' => $organizationId,
                'team.event_id' => $eventId
            ])
            ->get(['team.team_id', 'team.event_id', 'team.name']);

        return $this->getTeams($objects);
        //todo сделать выборку количества людей
    }

    public function getAll(): ?array
    {
    }

    /**@return int
     * @var $model Team
     */
    public function add(IModel $model): int
    {
        $object = TeamPdo::query()->create([
            'event_id' => $model->getEventId(),
            'name' => $model->getName()
        ]);

        return $object->getAttribute('team_id');
    }

    public function delete(int $id)
    {
        TeamPdo::query()->where('team_id', '=', $id)->delete();
    }

    /**
     * @param Team $model
     */
    public function update(IModel $model)
    {
        TeamPdo::query()->where('team_id', '=', $model->getId())->update([
            'name' => $model->getName()
        ]);
    }

    private function getTeams($teamsInObject)
    {
        $teams = [];

        foreach ($teamsInObject as $item) {
            $team = new Team($item['team_id'], $item['event_id'], $item['name']);
            $team->setCountOfPlayers($this->getCountOfPlayers($team->getId()));
            $teams[] = $team;
        }

        return $teams;
    }

    public function getAllForTeamLeadWithUserId(int $userId)
    {
        $results = TeamLead::query()
            ->join('team', 'team_lead.team_id', '=', 'team.team_id')
            ->join('event', 'event.event_id', '=', 'team.event_id')
            ->where('team_lead.user_id', '=', $userId)
            ->where('event.status', '!=', EventModel::COMPLETED)
            ->get([
                'team.team_id', 'team.event_id', 'team.name'
            ]);

        return $this->getTeams($results);
    }

    public function getAllForSecretaryWithUserId(int $userId)
    {
        $results = SecretaryOnOrgPDO::query()
            ->join('secretary', 'secretary.user_id', '=', 'secretary_on_organization.user_id')
            ->join('team', 'team.event_id', '=', 'secretary.event_id')
            ->where('secretary.user_id', '=', $userId)
            ->get();

        return $this->getTeams($results);
    }

    public function getAllForOrganizationId(int $organizationId)
    {
        $results = EventPDO::query()
            ->join('team', 'team.event_id', '=', 'event.event_id')
            ->where('event.organization_id', '=', $organizationId)
            ->get();

        return $this->getTeams($results);
    }

    public function confirm(int $teamId)
    {
        EventParticipant::query()
            ->where('team_id', '=', $teamId)
            ->update([
                'confirmed' => true
            ]);
    }

    private function getCountOfPlayers(int $teamId)
    {
        return EventParticipant::query()
            ->where('team_id', '=', $teamId)
            ->count();
    }
}