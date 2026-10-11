<?php

declare(strict_types=1);

namespace Simplify\Panel\Services;

use App\Models\Egg;
use Illuminate\Validation\ValidationException;

final class OtherGameCatalog
{
    public static function options(): array
    {
        return CreatorAccess::eggs()->pluck('name', 'id')->all();
    }

    public static function selected(mixed $id): ?Egg
    {
        return CreatorAccess::eggs()->find((int) $id);
    }

    public static function environment(Egg $egg, array $values): array
    {
        $result = [];
        $editable = [];
        foreach ($egg->variables as $variable) {
            $name = (string) $variable->env_variable;
            $editable[$name] = CreatorAccess::admin() || ($variable->user_editable && $variable->user_viewable);
            $value = $editable[$name] && array_key_exists($name, $values) ? $values[$name] : ($variable->default_value ?? '');
            if (!is_scalar($value) && $value !== null) {
                throw ValidationException::withMessages(['data.game_variables.' . $name => 'Enter a valid value.']);
            }
            $result[$name] = (string) ($value ?? '');
        }
        foreach ($values as $name => $value) {
            if (!array_key_exists($name, $editable) || !$editable[$name]) {
                throw ValidationException::withMessages(['data.game_variables' => 'This game variable cannot be changed.']);
            }
        }
        return $result;
    }

    public static function image(Egg $egg): string
    {
        $image = (string) (collect($egg->docker_images)->first() ?? '');
        if ($image === '') {
            throw ValidationException::withMessages(['data.game_egg_id' => 'This egg has no Docker image. Ask an administrator to configure it.']);
        }
        return $image;
    }
}
