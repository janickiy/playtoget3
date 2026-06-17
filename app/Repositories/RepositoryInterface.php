<?php

namespace App\Repositories;

use App\DTO\DataTransferObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    /**
     * Returns все записи модели; нужен для простых сценариев чтения без фильтров.
     */
    public function all(): Collection;

    /**
     * Ищет record by primary key; используется как базовый безопасный access к модели.
     */
    public function find(int|string $id): ?Model;

    /**
     * Creates record из массива атрибутов; общий низкоуровневый метод для наследников.
     */
    public function create(array $data): Builder|Model;

    /**
     * Creates record из DTO; нужен, чтобы record data шла через typeизированный контракт.
     */
    public function createFromDto(DataTransferObject $dto): Builder|Model;

    /**
     * Updates record by primary key массивом атрибутов; возвращает результат сохранения.
     */
    public function update(int|string $id, array $data): bool;

    /**
     * Updates record by primary key data из DTO; используется для typed update-операций.
     */
    public function updateFromDto(int|string $id, DataTransferObject $dto): bool;

    /**
     * Deletes record by primary key; возвращает false, если record не найдена.
     */
    public function delete(int|string $id): bool;

    /**
     * Deletes все записи модели без сброса sequence; нужен для очистки таблицы через query builder.
     */
    public function deleteAll(): void;

    /**
     * Completely очищает таблицу модели со сбросом счетчиков там, где это поддерживает driver.
     */
    public function truncate(): void;
}
