<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 03.11.2019
 * Time: 23:28
 */

namespace App\Services\EmailSendler;



use Exception;
use Monolog\Logger;
use Swift_Mailer;
use Swift_Message;
use Swift_Plugins_DecoratorPlugin;
use Swift_SmtpTransport;

class EmailSendler
{
    private $mailer;
    public static $MESSAGE_FOR_LOCAL_ADMIN_ON_CHANGE_ROLE = 'Вам была присвоена роль локального администратора для организации "organization_name"';
    public static $MESSAGE_FOR_PEOPLE_WHEN_SPORTSMEN_SEND_INVITE = 'Пользователь "user_name" c id user_id подал заявку на участие в мероприятии "event_name"';
    public static $MESSAGE_WHEN_DELETE_LOCAL_ADMIN = 'Вы были исключены из ряда локальных амдиинистраторов организации "organization_name"';
    public static $MESSAGE_FOR_PARTICIPANT_ON_ADDING_TO_EVENT_TO_TEAM = 'Вы были добавлены в команду "team_name" в мероприятии "event_name"';
    public static $MESSAGE_FOR_PARTICIPANT_ON_ADDING_TO_EVENT = 'Вы были добавлены в мероприятие "event_name"';
    public static $MESSAGE_FOR_PARTICIPANT_ON_CONFIRM_TEAM = 'Ваша команда "team_name" была утверждена в мероприятии "event_name"';
    public static $MESSAGE_FOR_PARTICIPANT_ON_CONFIRM = 'Ваша кандидатура были принята в мероприятие "event_name"';
    public static $MESSAGE_ON_DELETE_FROM_EVENT_FOR_PARTICIPANT = 'Вы были исключены из мероприятия "event_name"';
    public static $MESSAGE_FOR_TEAMLEAD_ON_ADDING = 'Вы были назначены тренером команды "team_name" в мероприятиии "event_name"';
    public static $MESSAGE_FOR_DELETING_TEAM_LEAD = 'Вы были исключены из числа тренеров команды "team_name" в мероприятии "event_name"';
    public static $MESSAGE_FOR_SECRETARY_ABOUT_ADDING_HIM = 'Вы были назначены секретарем в мероприятии "event_name"';
    public static $MESSAGE_FOR_SECRETARY_ABOUT_DELETE_HIM = 'Вы были исключены из числа секретарей мероприятия "event_name"';
    public static $MESSAGE_FOR_REFEREE_ON_ADDING_TRIAL_IN_EVENT = 'ВЫ были назначены судьей в мероприятии "event_name" для вида спорта: "trial_name", который пройдет "date_time" в месте "place", находящийся по адресу: "address"';
    public static $MESSAGE_FOR_REFEREE_ON_DELETING_FROM_TRIAL_IN_EVENT = 'Вы были исключены из числа судей в мероприятии "event_name", которые должны были судить "trial_name"';

    public function __construct($config)
    {
        $transport = (new Swift_SmtpTransport($config['host'], $config['port']))
            ->setUsername($config['login'])
            ->setPassword($config['password'])
            ->setEncryption('ssl');

        $this->mailer = new Swift_Mailer($transport);
    }

    public function sendMessage(array $peoples, string $messageText)
    {
        /*$decorator = new Swift_Plugins_DecoratorPlugin($peoples);
        $this->mailer->registerPlugin($decorator);*/
        $message = (new Swift_Message('Invite to registration on GTO service'));
        $message->setFrom(['gto_service@gtoservice.ru' => 'GTO']);
        foreach ($peoples as $people){
            $message->addTo($people);
        }

        $message->setSubject('GTO Service');
        $message->setBody($messageText, 'text/html');
        $this->mailer->send($message);
    }

    public function sendInvite($email, $token)
    {
        $message = (new Swift_Message('Invite to registration on GTO service'))
            ->setFrom(['gto_service@gtoservice.ru' => 'GTO'])
            ->setTo([$email => ''])
            ->setBody('чтобы зарегистрироваться, пройдите по ссылке: <a href="http://gtoservice.ru/registration/confirm?token='.$token.'&email='.$email.'">ссылка</a>', 'text/html');

        $failedRecipients = [];
        $this->mailer->send($message, $failedRecipients);
        if (count($failedRecipients) != 0){
            throw new Exception();
        }
    }
}