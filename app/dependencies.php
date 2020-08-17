<?php
declare(strict_types=1);

use App\Application\Actions\EventParticipant\EventParticipantAction;
use App\Application\Actions\Referee\RefereeAction;
use App\Application\Actions\Result\ResultAction;
use App\Application\Actions\SportObject\SportObjectAction;
use App\Application\Actions\TeamLead\TeamLeadAction;
use App\Persistance\Repositories\AgeCategory\AgeCategoryRepository;
use App\Persistance\Repositories\EventParticipant\EventParticipantRepository;
use App\Persistance\Repositories\Referee\RefereeInTrialOnEventRepository;
use App\Persistance\Repositories\Referee\RefereeRepository;
use App\Persistance\Repositories\Result\ResultRepository;
use App\Persistance\Repositories\Secretary\SecretaryOnOrganizationRepository;
use App\Persistance\Repositories\SportObject\SportObjectRepository;
use App\Persistance\Repositories\TeamLead\TeamLeadRepository;
use App\Persistance\Repositories\TrialRepository\TableInEventRepository;
use App\Persistance\Repositories\TrialRepository\TableRepository;
use App\Persistance\Repositories\TrialRepository\TrialInEventRepository;
use App\Services\AccessService\AccessService;
use App\Services\EventParticipant\EventParticipantService;
use App\Services\Referee\RefereeService;
use App\Services\Result\ResultService;
use App\Services\SportObject\SportObjectService;
use App\Services\TeamLead\TeamLeadService;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use App\Swagger\SwaggerWatcher;
use App\Application\Actions\Swagger;
use App\Persistance\ModelsEloquant\DataBase;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Services\EmailSendler\EmailSendler;
use App\Services\Token\Token;
use \App\Application\Actions\User\AuthAction;
use \App\Persistance\Repositories\User\UserRepository;
use App\Persistance\Repositories\User\RefreshTokenRepository;
use App\Persistance\Repositories\User\RegistrationTokenRepository;
use App\Application\Actions\Trial\TrialAction;
use App\Services\Trial\Trial;
use App\Persistance\Repositories\TrialRepository\TrialRepository;
use App\Application\Actions\Role\RoleAction;
use App\Persistance\Repositories\Role\RoleRepository;
use App\Application\Actions\Invite\InviteAction;
use App\Services\Invite\Invite;
use App\Services\Role\Role;
use App\Services\Auth\Auth;
use App\Services\Organization\OrganizationService;
use App\Application\Actions\Organization\OrganizationAction;
use App\Persistance\Repositories\Organization\OrganizationRepository;
use App\Persistance\Repositories\LocalAdmin\LocalAdminRepository;
use App\Services\LocalAdmin\LocalAdminService;
use App\Application\Actions\LocalAdmin\LocalAdminAction;
use App\Application\Actions\Event\EventAction;
use App\Services\Event\EventService;
use App\Persistance\Repositories\Event\EventRepository;
use App\Persistance\Repositories\Team\TeamRepository;
use App\Services\Team\TeamService;
use App\Application\Actions\Team\TeamAction;
use App\Services\Secretary\SecretaryService;
use App\Persistance\Repositories\Secretary\SecretaryRepository;
use App\Application\Actions\Secretary\SecretaryAction;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        SecretaryAction::class => function(ContainerInterface $c){
            return new SecretaryAction($c->get(SecretaryService::class), $c->get(AccessService::class));
        },
        EventParticipantAction::class => function(ContainerInterface $c){
            return new EventParticipantAction($c->get(AccessService::class), $c->get(EventParticipantService::class));
        },
        EventParticipantService::class => function(ContainerInterface $c){
            return new EventParticipantService($c->get(EventParticipantRepository::class), $c->get(UserRepository::class), $c->get(TeamRepository::class), $c->get(EmailSendler::class), $c->get(EventRepository::class));
        },
        SecretaryService::class => function(ContainerInterface $c){
            return new SecretaryService(
                $c->get(SecretaryRepository::class),
                $c->get(UserRepository::class),
                $c->get(OrganizationRepository::class),
                $c->get(LocalAdminRepository::class),
                $c->get(EventRepository::class),
                $c->get(RoleRepository::class),
                $c->get(SecretaryOnOrganizationRepository::class),
                $c->get(EmailSendler::class)
            );
        },
        SecretaryOnOrganizationRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new SecretaryOnOrganizationRepository();
        },
        SecretaryRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new SecretaryRepository();
        },
        LocalAdminAction::class => function(ContainerInterface $c)
        {
            return new LocalAdminAction($c->get(LocalAdminService::class));
        },
        LocalAdminService::class => function(ContainerInterface $c)
        {
            return new LocalAdminService($c->get(LocalAdminRepository::class), $c->get(RoleRepository::class), $c->get(UserRepository::class), $c->get(OrganizationRepository::class), $c->get(EmailSendler::class));
        },
        LocalAdminRepository::class => function(ContainerInterface $c)
        {
            $c->get(DataBase::class);
            return new LocalAdminRepository();
        },
        OrganizationRepository::class => function(ContainerInterface $c)
        {
            $c->get(DataBase::class);
            return new OrganizationRepository();
        },
        OrganizationService::class => function(ContainerInterface $c)
        {
            return new OrganizationService(
                $c->get(OrganizationRepository::class),
                $c->get(SecretaryRepository::class),
                $c->get(LocalAdminRepository::class),
                $c->get(UserRepository::class),
                $c->get(RoleRepository::class)
            );
        },
        OrganizationAction::class => function(ContainerInterface $c)
        {
            return new OrganizationAction($c->get(OrganizationService::class));
        },
        TeamAction::class => function(ContainerInterface $c){
            return new TeamAction($c->get(TeamService::class), $c->get(AccessService::class));
        },
        AccessService::class => function(ContainerInterface $c){
            return new AccessService(
                $c->get(UserRepository::class),
                $c->get(LocalAdminRepository::class),
                $c->get(SecretaryRepository::class),
                $c->get(OrganizationRepository::class),
                $c->get(RoleRepository::class),
                $c->get(EventRepository::class),
                $c->get(EventParticipantRepository::class),
                $c->get(TeamRepository::class),
                $c->get(TeamLeadRepository::class),
                $c->get(SecretaryOnOrganizationRepository::class),
                $c->get(SportObjectRepository::class),
                $c->get(RefereeRepository::class),
                $c->get(TableInEventRepository::class),
                $c->get(TableRepository::class),
                $c->get(TrialInEventRepository::class),
                $c->get(RefereeInTrialOnEventRepository::class),
                $c->get(ResultRepository::class)
            );
        },
        TrialInEventRepository::class => function(ContainerInterface $c)
        {
            $c->get(DataBase::class);
            return new TrialInEventRepository();
        },
        TeamService::class => function(ContainerInterface $c){
            return new TeamService(
                $c->get(UserRepository::class),
                $c->get(TeamRepository::class),
                $c->get(EventRepository::class),
                $c->get(LocalAdminRepository::class),
                $c->get(TeamLeadRepository::class),
                $c->get(RoleRepository::class),
                $c->get(EventParticipantRepository::class),
                $c->get(EmailSendler::class)
            );
        },
        ResultAction::class => function(ContainerInterface $c){
            return new ResultAction($c->get(ResultService::class), $c->get(AccessService::class));
        },
        ResultService::class => function(ContainerInterface $c){
            return new ResultService(
                $c->get(LocalAdminRepository::class),
                $c->get(EventRepository::class),
                $c->get(SecretaryRepository::class),
                $c->get(RoleRepository::class),
                $c->get(UserRepository::class),
                $c->get(EventParticipantRepository::class),
                $c->get(SecretaryOnOrganizationRepository::class),
                $c->get(TableInEventRepository::class),
                $c->get(TableRepository::class),
                $c->get(TrialRepository::class),
                $c->get(TrialInEventRepository::class),
                $c->get(SportObjectRepository::class),
                $c->get(RefereeInTrialOnEventRepository::class),
                $c->get(ResultRepository::class),
                $c->get(TeamRepository::class),
                $c->get(AgeCategoryRepository::class)
            );
        },
        AgeCategoryRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new AgeCategoryRepository();
        },
        ResultRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new ResultRepository($c->get(TrialInEventRepository::class), $c->get(UserRepository::class));
        },
        RefereeAction::class => function (ContainerInterface $c){
            return new RefereeAction($c->get(AccessService::class), $c->get(RefereeService::class));
        },
        RefereeService::class => function(ContainerInterface $c){
            return new RefereeService($c->get(RefereeRepository::class), $c->get(UserRepository::class), $c->get(RefereeInTrialOnEventRepository::class), $c->get(EventRepository::class), $c->get(EmailSendler::class), $c->get(TrialInEventRepository::class));
        },
        RefereeRepository::class =>function(ContainerInterface$c){
            $c->get(DataBase::class);
            return new RefereeRepository();
        },
        EventService::class => function(ContainerInterface $c)
        {
            return new EventService(
                $c->get(LocalAdminRepository::class),
                $c->get(EventRepository::class),
                $c->get(SecretaryRepository::class),
                $c->get(RoleRepository::class),
                $c->get(UserRepository::class),
                $c->get(EventParticipantRepository::class),
                $c->get(SecretaryOnOrganizationRepository::class),
                $c->get(TableInEventRepository::class),
                $c->get(TableRepository::class),
                $c->get(TrialRepository::class),
                $c->get(TrialInEventRepository::class),
                $c->get(SportObjectRepository::class),
                $c->get(RefereeInTrialOnEventRepository::class),
                $c->get(ResultRepository::class),
                $c->get(EmailSendler::class)
            );
        },
        SportObjectAction::class => function(ContainerInterface $c)
        {
            return new SportObjectAction($c->get(SportObjectService::class), $c->get(AccessService::class));
        },
        SportObjectService::class => function(ContainerInterface $c)
        {
            return new SportObjectService($c->get(SportObjectRepository::class));
        },
        TableInEventRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new TableInEventRepository();
        },
        RefereeInTrialOnEventRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new RefereeInTrialOnEventRepository();
        },
        SportObjectRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new SportObjectRepository();
        },
        EventAction::class => function(ContainerInterface $c)
        {
            return new EventAction($c->get(EventService::class), $c->get(AccessService::class));
        },
        InviteAction::class => function(ContainerInterface $c){
            return new InviteAction($c->get(Invite::class));
        },
        Invite::class => function(ContainerInterface $c){
            $c->get(Token::class);
            return new Invite($c->get(RegistrationTokenRepository::class), $c->get(EmailSendler::class), $c->get(UserRepository::class), $c->get(RoleRepository::class));
        },
        RoleAction::class => function(ContainerInterface $c){
            return new RoleAction($c->get(Role::class));
        },
        AuthAction::class => function(ContainerInterface $c){
            $c->get(Token::class);
            return new AuthAction($c->get(Auth::class));
        },
        Auth::class => function(ContainerInterface $c){
            return new Auth($c->get(UserRepository::class), $c->get(RefreshTokenRepository::class), $c->get(RegistrationTokenRepository::class), $c->get(LocalAdminRepository::class));
        },
        TeamRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new TeamRepository();
        },
        RegistrationTokenRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new RegistrationTokenRepository();
        },
        TeamLeadRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new TeamLeadRepository();
        },
        EventParticipantRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new EventParticipantRepository();
        },
        TeamLeadAction::class => function(ContainerInterface $c){
            return new TeamLeadAction($c->get(TeamLeadService::class), $c->get(AccessService::class));
        },
        TeamLeadService::class => function(ContainerInterface $c){
            return new TeamLeadService($c->get(TeamLeadRepository::class), $c->get(UserRepository::class), $c->get(RoleRepository::class), $c->get(TeamRepository::class), $c->get(EventRepository::class), $c->get(EmailSendler::class));
        },
        RefreshTokenRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new RefreshTokenRepository();
        },
        Role::class => function(ContainerInterface $c){
            return new Role($c->get(RoleRepository::class));
        },
        EventRepository::class => function(ContainerInterface $c) {
            $c->get(DataBase::class);
            return new EventRepository();
        },
        RoleRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new RoleRepository();
        },
        TrialAction::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new TrialAction(new Trial(new TrialRepository(), $c->get(TableRepository::class)));
        },
        TableRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new TableRepository();
        },
        UserRepository::class => function(ContainerInterface $c){
            $c->get(DataBase::class);
            return new UserRepository();
        },
        Token::class => function(ContainerInterface $c){
            Token::$key = $c->get('privateSettings')['Token']['key'];
        },
        EmailSendler::class => function(ContainerInterface $c){
            return new EmailSendler($c->get('privateSettings')['Mailer']);
        },
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get('settings');

            $loggerSettings = $settings['logger'];
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        DataBase::class => function(ContainerInterface $c):Capsule{
            $db = new DataBase($c->get('privateSettings')['DB']);
            return $db->getCapsule();
        },
        SwaggerWatcher::class => function(ContainerInterface $c){
            $logger = new Logger('a');
            $swaggerAction = new Swagger\SwaggerAction($c->get('settings')['pathToProject']);
            $logger->alert('refre');
            return $swaggerAction;
        }
    ]);
};
