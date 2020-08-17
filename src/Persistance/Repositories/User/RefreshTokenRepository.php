<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 05.11.2019
 * Time: 2:00
 */

namespace App\Persistance\Repositories\User;
use App\Domain\Models\IModel;
use App\Domain\Models\IRepository;
use App\Domain\Models\Organization;
use App\Persistance\ModelsEloquant\LoginToken\RefreshToken as RToken;

class RefreshTokenRepository implements IRepository
{
    public function refreshTokenIsSet(string $token):bool
    {
        $result = RToken::query()->where('token', '=', $token)->get();
        if (!isset($result[0]->token)){
            return false;
        }

        return true;
    }

    public function deleteRefreshToken(string $token):void
    {
        RToken::query()->where('token', '=', $token)->delete();
    }

    public function deleteRefreshTokenWithEmail(string $email):void
    {
        RToken::query()->where('email', '=', $email)->delete();
    }

    public function addRefreshToken(string $token, string $email):void
    {
        RToken::query()->create([
            'token' => $token,
            'email' => $email
        ]);
    }

    public function updateRefreshTokenWithEmail(string $email, string $token):void
    {
        RToken::query()->where('email', '=', $email)->update([
            'token' => $token
        ]);
    }

    public function get(int $id): IModel
    {
        // TODO: Implement get() method.
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        // TODO: Implement getAll() method.
    }

    public function add(IModel $model):int
    {
        // TODO: Implement add() method.
    }

    public function delete(int $id)
    {
        // TODO: Implement delete() method.
    }

    public function update(IModel $organization)
    {
        // TODO: Implement update() method.
    }
}