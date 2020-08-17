<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 28.11.2019
 * Time: 6:49
 */

namespace App\Application\Actions\Trial;


use App\Application\Actions\Action;
use App\Services\Trial\Trial;
use App\Validators\Trial\GetSecondResultValidator;
use App\Validators\Trial\GetTrialsValidator;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;
use Symfony\Component\Validator\Constraints as Assert;

class TrialAction extends Action
{
    private $trialService;

    public function __construct(Trial $trial)
    {
        $this->trialService = $trial;
    }

    /**
     *
     * * @SWG\Get(
     *   path="/api/v1/trial/{age}/{gender}",
     *   summary="получение списка испытаний для определенного пола и возраста",
     *   operationId="получение списка испытаний для определенного пола и возраста",
     *   tags={"Trial"},
     *   @SWG\Response(response=200, description="OK", @SWG\Schema(
     *              @SWG\Property(property="groups", type="array", @SWG\Items(
     *                  @SWG\Property(property="necessary", type="boolean"),
     *                  @SWG\Property(property="group", type="array", @SWG\Items(
     *                      @SWG\Property(property="trialName", type="string"),
     *                      @SWG\Property(property="trialId", type="integer"),
     *                      @SWG\Property(property="resultForBronze", type="number"),
     *                      @SWG\Property(property="resultForSilver", type="number"),
     *                      @SWG\Property(property="resultForGold", type="number"),
     *                      @SWG\Property(property="typeTime", type="boolean")
     *                  ))
     *              )),
     *              @SWG\Property(property="ageCategory", type="string")
     *
     *     )),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     ))
     * )
     *
     */
    public function getTrialsByGenderAndAge(Request $request, Response $response, $args): Response
    {
        $params = [
            'gender' => (int)$args['gender'],
            'age' => (int)$args['age']
        ];

        $validator = new GetTrialsValidator();
        $errors = $validator->validate($params);

        if (count($errors) > 0){
            return $this->respond(400, ['errors' => $errors], $response);
        }

        return $this->trialService->getTrialsByGenderAndAge($params, $response);
    }

    /**
     *
     * * @SWG\Get(
     *   path="/api/v1/trial/{id}/firstResult",
     *   summary="Получение вторичного результата по испытанию исходя из первичного результата из таблицы по переводу",
     *   operationId="Получение вторичного результата по испытанию исходя из первичного результата из таблицы по переводу",
     *   tags={"Trial"},
     *   @SWG\Parameter(in="query", name="firstResult", type="integer"),
     *   @SWG\Response(response=200, description="OK", @SWG\Schema(
     *          @SWG\Property(property="secondResult", type="number")
     *     )),
     *  @SWG\Response(response=400, description="Error", @SWG\Schema(
     *          @SWG\Property(property="errors", type="array", @SWG\Items(
     *              @SWG\Property(property="type", type="string"),
     *              @SWG\Property(property="description", type="string")
     *          ))
     *     )))
     * )
     *
     */
    public function getSecondResult(Request $request, Response $response, $args): Response
    {
        $firsResult = explode('=', $request->getUri()->getQuery())[1] ?? null;

        $params = [
            'firstResult' => $firsResult,
            'id' => (int)$args['id']
        ];

        $validator = new GetSecondResultValidator();
        $errors = $validator->validate($params);

        if (count($errors) > 0){
            return $this->respond(400, ['errors' => $errors], $response);
        }

        return $this->trialService->getSecondResult($params, $response);
    }

    /**
     *  @SWG\Get(
     *   path="/api/v1/tables",
     *   summary="получение всех доступных таблиц перевода",
     *   tags={"Tables"},
     *   @SWG\Response(response=200, description="OK", @SWG\Property(type="array", @SWG\Items(ref="#/definitions/table")),
     *  )
     * )
     */
    public function getAllFreeTables(Request $request, Response $response, $args): Response
    {
        $tables = $this->trialService->getAllFreeTables();
        $tablesForResponse = [];
        foreach ($tables as $table){
            $tablesForResponse[] = $table->toArray();
        }

        return $this->respond(200, $tablesForResponse, $response);
    }
}