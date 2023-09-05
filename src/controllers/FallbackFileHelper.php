<?php

declare(strict_types=1);

namespace bizley\migration\controllers;

use yii\base\InvalidArgumentException;
use yii\helpers\ArrayHelper;

class FallbackFileHelper
{
    /**
     * @param string $path
     * @param int|string|array<int|string, int|string>|null $ownership
     * @param int|null $mode
     * @throws \Exception
     */
    public static function changeOwnership(string $path, $ownership, ?int $mode): void
    {
        if ($mode === null && ($ownership === null || $ownership === '' || $ownership === [])) {
            return;
        }

        $user = $group = null;
        if (!empty($ownership) || $ownership === 0 || $ownership === '0') {
            if (\is_int($ownership)) {
                $user = $ownership;
            } elseif (\is_string($ownership)) {
                $ownerParts = \explode(':', $ownership);
                $user = $ownerParts[0];
                if (\count($ownerParts) > 1) {
                    $group = $ownerParts[1];
                }
            } elseif (\is_array($ownership)) {
                $ownershipIsIndexed = ArrayHelper::isIndexed($ownership);
                $user = ArrayHelper::getValue($ownership, $ownershipIsIndexed ? '0' : 'user');
                $group = ArrayHelper::getValue($ownership, $ownershipIsIndexed ? '1' : 'group');
            } else {
                throw new InvalidArgumentException('fileOwnership option must be an integer, string, array, or null.');
            }
        }

        if ($mode !== null && !\chmod($path, $mode)) {
            throw new \RuntimeException('Unable to change mode of "' . $path . '" to "0' . \decoct($mode) . '".');
        }
        if ($user !== null && $user !== '') {
            if (\is_numeric($user)) {
                $user = (int)$user;
            } elseif (!\is_string($user)) {
                throw new InvalidArgumentException(
                    'The user part of fileOwnership option must be an integer, string, or null.'
                );
            }
            if (!\chown($path, $user)) {
                throw new \RuntimeException('Unable to change user ownership of "' . $path . '" to "' . $user . '".');
            }
        }
        if ($group !== null && $group !== '') {
            if (\is_numeric($group)) {
                $group = (int)$group;
            } elseif (!\is_string($group)) {
                throw new InvalidArgumentException(
                    'The group part of fileOwnership option must be an integer, string, or null.'
                );
            }
            if (!\chgrp($path, $group)) {
                throw new \RuntimeException('Unable to change group ownership of "' . $path . '" to "' . $group . '".');
            }
        }
    }
}
