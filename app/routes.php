<?php
declare(strict_types=1);

use App\Application\Actions\EventParticipant\EventParticipantAction;
use App\Application\Actions\Referee\RefereeAction;
use App\Application\Actions\Result\ResultAction;
use App\Application\Actions\SportObject\SportObjectAction;
use App\Application\Actions\TeamLead\TeamLeadAction;
use Slim\App;
use App\Swagger\SwaggerWatcher;
use \App\Application\Actions\User\AuthAction;
use App\Application\Actions\Trial\TrialAction;
use App\Application\Actions\Role\RoleAction;
use App\Application\Actions\Invite\InviteAction;
use App\Application\Actions\Organization\OrganizationAction;
use App\Application\Actions\LocalAdmin\LocalAdminAction;
use App\Application\Actions\Event\EventAction;
use App\Application\Actions\Team\TeamAction;
use App\Application\Actions\Secretary\SecretaryAction;

return function (App $app) {
    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });

    $app->post('/api/v1/invite', InviteAction::class.':sendInviteToOrganization');
    $app->post('/api/v1/invite/isValid', InviteAction::class.':validate');

    //работа с пользователями
    $app->post('/api/v1/auth/registration', AuthAction::class.':registration');
    $app->post('/api/v1/auth/login', AuthAction::class.':login');
    $app->post('/api/v1/auth/refresh', AuthAction::class.':refresh');
    $app->post('/api/v1/auth/invite', InviteAction::class.':sendInviteToRegistration');
    $app->post('/api/v1/auth/confirmAccount', AuthAction::class.':confirmAccount');
    $app->post('/api/v1/auth/info', AuthAction::class.':getInfo');

    //result
    $app->get('/api/v1/trial/{age:[0-9]+}/{gender:[0-9]+}', TrialAction::class.':getTrialsByGenderAndAge');
    $app->get('/api/v1/trial/{id:[0-9]+}/firstResult', TrialAction::class.':getSecondResult');
    $app->get('/api/v1/event/{eventId}/allResults', ResultAction::class.':getAllResults');
    $app->get('/api/v1/event/{eventId}/allResults/csv', ResultAction::class.':getAllResultsInXlsx');

    $app->get('/docs', SwaggerWatcher::class.':getNewDocs');

    //organization
    $app->post('/api/v1/organization', OrganizationAction::class.':add');
    $app->get('/api/v1/organization/{id:[0-9]+}', OrganizationAction::class.':get');
    $app->delete('/api/v1/organization/{id:[0-9]+}', OrganizationAction::class.':delete');
    $app->get('/api/v1/organization', OrganizationAction::class.':getAll');
    $app->put('/api/v1/organization/{id:[0-9]+}', OrganizationAction::class.':update');

    //localAdmin
    $app->post('/api/v1/organization/{id:[0-9]+}/localAdmin/existingAccount', LocalAdminAction::class.':addExistingAccount');
    $app->post('/api/v1/organization/{id:[0-9]+}/localAdmin', LocalAdminAction::class.':add');
    $app->get('/api/v1/organization/{id:[0-9]+}/localAdmin', LocalAdminAction::class.':getAll');
    $app->get('/api/v1/organization/{id:[0-9]+}/localAdmin/{idLocalAdmin:[0-9]+}', LocalAdminAction::class.':get');
    $app->delete('/api/v1/organization/{id:[0-9]+}/localAdmin/{idLocalAdmin:[0-9]+}', LocalAdminAction::class.':delete');
    $app->put('/api/v1/organization/{id:[0-9]+}/localAdmin/{idLocalAdmin:[0-9]+}', OrganizationAction::class.':update');

    //Event
    $app->post('/api/v1/organization/{id:[0-9]+}/event', EventAction::class.':add');
    $app->delete('/api/v1/organization/{id:[0-9]+}/event/{eventId:[0-9]+}', EventAction::class.':delete');
    $app->get('/api/v1/organization/{id:[0-9]+}/event/{eventId:[0-9]+}', EventAction::class.':get');
    $app->put('/api/v1/organization/{id:[0-9]+}/event/{eventId:[0-9]+}', EventAction::class.':update');
    $app->get('/api/v1/organization/{id:[0-9]+}/event', EventAction::class.':getAll');
    $app->get('/api/v1/event/forSecretary', EventAction::class.':getForSecretary');
    $app->get('/api/v1/event/forUser', EventAction::class.':getForUser');
    $app->get('/api/v1/event/{eventId:[0-9]+}/table', EventAction::class.':getTable');
    $app->post('/api/v1/event/{eventId:[0-9]+}/table/{tableId:[0-9]+}', EventAction::class.':addTable');
    $app->get('/api/v1/event/{eventId:[0-9]+}/freeTrials', EventAction::class.':getFreeTrials');
    $app->post('/api/v1/event/{eventId:[0-9]+}/trial', EventAction::class.':addTrialToEvent');
    $app->get('/api/v1/event/{eventId:[0-9]+}/trial', EventAction::class.':getTrials');
    $app->delete('/api/v1/trialInEvent/{trialInEventId:[0-9]+}', EventAction::class.':deleteTrialFromEvent');
    $app->post('/api/v1/event/{eventId}/changeStatus', EventAction::class.':changeStatusOfEvent');
    //Team
    $app->post('/api/v1/organization/{id:[0-9]+}/event/{eventId:[0-9]+}/team', TeamAction::class.':add');
    $app->get('/api/v1/organization/{id:[0-9]+}/event/{eventId:[0-9]+}/team', TeamAction::class.':getAll');
    $app->get('/api/v1/team/{teamId:[0-9]+}', TeamAction::class.':get');
    $app->delete('/api/v1/team/{teamId:[0-9]+}', TeamAction::class.':delete');
    $app->put('/api/v1/team/{teamId:[0-9]+}', TeamAction::class.':update');
    $app->get('/api/v1/team', TeamAction::class.':getListForUser');
    $app->post('/api/v1/team/{teamId:[0-9]+}/confirm', TeamAction::class.':confirm');

    //Secretary
    $app->post('/api/v1/organization/{id:[0-9]+}/secretary', SecretaryAction::class.':addToOrganization');
    $app->get('/api/v1/organization/{id:[0-9]+}/secretary/{secretaryId:[0-9]+}', SecretaryAction::class.':getSecretaryOnOrganization');
    $app->get('/api/v1/organization/{id:[0-9]+}/secretary', SecretaryAction::class.':getSecretariesOnOrganization');
    $app->delete('/api/v1/organization/{id:[0-9]+}/secretary/{secretaryId:[0-9]+}', SecretaryAction::class.':deleteFromOrganization');
    $app->post('/api/v1/organization/{id:[0-9]+}/event/{eventId:[0-9]+}/secretary/{secretaryOnOrganizationId:[0-9]+}', SecretaryAction::class.':addToEvent');
    $app->get('/api/v1/organization/{id:[0-9]+}/event/{eventId:[0-9]+}/secretary', SecretaryAction::class.':get');
    $app->delete('/api/v1/organization/{id:[0-9]+}/event/{eventId:[0-9]+}/secretary/{secretaryId:[0-9]+}', SecretaryAction::class.':delete');

    //SportObjects
    $app->post('/api/v1/organization/{id:[0-9]+}/sportObject', SportObjectAction::class.':create');
    $app->get('/api/v1/organization/{id:[0-9]+}/sportObject', SportObjectAction::class.':get');
    $app->delete('/api/v1/organization/{id:[0-9]+}/sportObject/{sportObjectId:[0-9]+}', SportObjectAction::class.':delete');
    $app->put('/api/v1/organization/{id:[0-9]+}/sportObject/{sportObjectId:[0-9]+}', SportObjectAction::class.':update');

    //referee
    $app->post('/api/v1/organization/{id:[0-9]+}/referee', RefereeAction::class.':create');
    $app->get('/api/v1/organization/{id:[0-9]+}/referee', RefereeAction::class.':get');
    $app->delete('/api/v1/organization/{id:[0-9]+}/referee/{refereeId:[0-9]+}', RefereeAction::class.':delete');

    //eventParticipant
    $app->put('/api/v1/participant/{participantId:[0-9]+}', EventParticipantAction::class.':updateUserOnTeam');
    $app->post('/api/v1/event/{eventId:[0-9]+}/apply', EventAction::class.':apply');
    $app->post('/api/v1/event/{eventId:[0-9]+}/unsubscribe', EventAction::class.':unsubscribe');
    $app->post('/api/v1/team/{teamId:[0-9]+}/participant', EventParticipantAction::class.':add');
    $app->post('/api/v1/event/{eventId:[0-9]+}/participant', EventParticipantAction::class.':addParticipantWithoutTeam');
    $app->get('/api/v1/event/{eventId:[0-9]+}/participant', EventParticipantAction::class.':getAllForEvent');
    $app->post('/api/v1/participant/{participantId:[0-9]+}', EventParticipantAction::class.':confirmApply');
    $app->delete('/api/v1/participant/{participantId:[0-9]+}', EventParticipantAction::class.':deleteParticipant');
    $app->get('/api/v1/team/{teamId:[0-9]+}/participant', EventParticipantAction::class.':getAllForTeam');

    //TeamLead
    $app->post('/api/v1/team/{teamId:[0-9]+}/teamLead', TeamLeadAction::class.':add');
    $app->get('/api/v1/team/{teamId:[0-9]+}/teamLead', TeamLeadAction::class.':getAllForTeam');
    $app->delete('/api/v1/teamLead/{teamLeadId:[0-9]+}', TeamLeadAction::class.':delete');

    //referee
    $app->post('/api/v1/trialInEvent/{trialInEventId:[0-9]+}/refereeInOrganization/{refereeInOrganizationId:[0-9]+}', RefereeAction::class.':addRefereeToTrialInEvent');
    $app->delete('/api/v1/refereeInTrialOnEvent/{id:[0-9]+}', RefereeAction::class.':deleteRefereeFromTrialInEvent');

    //tables
    $app->get('/api/v1/tables', TrialAction::class.':getAllFreeTables');

    //роли
    $app->get('/api/v1/role', RoleAction::class.':getList');

    //result
    $app->get('/api/v1/event/{eventId:[0-9]+}/user/{userId:[0-9]+}/result', ResultAction::class.':getResultsOfUserInEvent');
    $app->get('/api/v1/trialInEvent/{trialInEventId:[0-9]+}/result', ResultAction::class.':getResultsOnTrialInEvent');
    $app->put('/api/v1/resultTrialInEvent/{resultTrialInEventId:[0-9]+}', ResultAction::class.':updateResult');

};
