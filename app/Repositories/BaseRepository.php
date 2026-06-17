<?php

namespace App\Repositories;


use App\DTO\DataTransferObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository implements RepositoryInterface
{

    /**
     * Сохраняет Eloquent-модель, с которой работает конкретный репозиторий.
     *
     * @param Model $model
     */
    public function __construct(protected Model $model)
    {
    }

    /**
     * Creates новую record из массива атрибутов; базовая операция для всех наследников.
     *
     * @param array $data
     * @return Builder|Model
     */
    public function create(array $data): Builder|Model
    {
        return $this->model->create($data);
    }

    /**
     * Creates новую record из DTO; нужен, чтобы сервисы не passed сырые массивы напрямую.
     */
    public function createFromDto(DataTransferObject $dto): Builder|Model
    {
        return $this->create($dto->toArray());
    }

    /**
     * Updates record по id provided атрибутами; возвращает false, если record не найдена.
     *
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): bool
    {
        $model = $this->model->find($id);

        if ($model) {
            return $model->fill($data)->save();
        }

        return false;
    }

    /**
     * Updates record по id data из DTO; нужен для единого typed update-approach.
     */
    public function updateFromDto(int|string $id, DataTransferObject $dto): bool
    {
        return $this->update($id, $dto->toArray());
    }

    /**
     * Returns все записи таблицы модели; используется только для простых выборок без фильтров.
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Ищет record by primary key и возвращает null, если record отсутствует.
     *
     * @return Model|null
     */
    public function find(int|string $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Deletes record by primary key; нужен для общей операции deletion в наследниках.
     */
    public function delete(int|string $id): bool
    {
        $model = $this->model->find($id);
        if ($model) {
            $model->delete();
            return true;
        }
        return false;
    }

    /**
     * Deletes все записи через query builder без переcreation таблицы.
     */
    public function deleteAll(): void
    {
        $this->model->query()->delete();
    }

    /**
     * Completely очищает таблицу модели; полезно для служебной очистки и тестов.
     */
    public function truncate(): void
    {
        $this->model->truncate();
    }

}
