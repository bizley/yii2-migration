<?php

declare(strict_types=1);

namespace bizley\tests\unit\controllers;

use bizley\migration\controllers\FallbackFileHelper;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidArgumentException;

/** @group controller */
final class FallbackFileHelperTest extends TestCase
{
    public $basePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'runtime';

    protected function createFileStructure(array $items, $basePath = ''): void
    {
        if (empty($basePath)) {
            $basePath = $this->basePath;
        }
        foreach ($items as $name => $content) {
            $itemName = $basePath . DIRECTORY_SEPARATOR . $name;
            if (\is_array($content)) {
                if (@\mkdir($itemName, 0777, true) === false) {
                    self::markTestSkipped("Permission denied to create folder $itemName");
                }
                $this->createFileStructure($content, $itemName);
            } else {
                \file_put_contents($itemName, $content);
            }
        }
    }

    protected function removeDir($dirName): void
    {
        if (!empty($dirName) && \is_dir($dirName) && $handle = \opendir($dirName)) {
            while (false !== ($entry = \readdir($handle))) {
                if ($entry !== '.' && $entry !== '..') {
                    $item = $dirName . DIRECTORY_SEPARATOR . $entry;
                    if (\is_dir($item) === true && !\is_link($item)) {
                        $this->removeDir($item);
                    } else {
                        \unlink($item);
                    }
                }
            }
            \closedir($handle);
            if (\substr($dirName, -7) !== 'runtime') {
                \rmdir($dirName);
            }
        }
    }


    public function tearDown(): void
    {
        $this->removeDir($this->basePath);
    }

    /**
     * @test
     */
    public function shouldChangeOwnership(): void
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            self::markTestSkipped('FallbackFileHelper::changeOwnership() fails silently on Windows, nothing to test.');
        }

        if (!extension_loaded('posix')) {
            self::markTestSkipped('posix extension is required.');
        }

        $dirName = 'change_ownership_test_dir';
        $fileName = 'file_1.txt';
        $testFile = $this->basePath . DIRECTORY_SEPARATOR . $dirName . DIRECTORY_SEPARATOR . $fileName;

        $currentUserId = \posix_getuid();
        $currentUserName = \posix_getpwuid($currentUserId)['name'];
        $currentGroupId = \posix_getgid();
        $currentGroupName = \posix_getgrgid($currentGroupId)['name'];

        $this->createFileStructure(
            [
                $dirName => [
                    $fileName => 'test 1',
                ],
            ]
        );

        // Ensure the test file is created as the current user/group and has a specific file mode
        self::assertFileExists($testFile);
        $fileMode = 0770;
        \chmod($testFile, $fileMode);
        \clearstatcache(true, $testFile);
        self::assertEquals($currentUserId, \fileowner($testFile), 'Expected created test file owner to be current user.');
        self::assertEquals($currentGroupId, \filegroup($testFile), 'Expected created test file group to be current group.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be changed.'
        );


        // Test file mode
        $fileMode = 0777;
        FallbackFileHelper::changeOwnership($testFile, null, $fileMode);
        \clearstatcache(true, $testFile);
        self::assertEquals($currentUserId, \fileowner($testFile), 'Expected file owner to be unchanged.');
        self::assertEquals($currentGroupId, \filegroup($testFile), 'Expected file group to be unchanged.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be changed.'
        );

        \chmod($this->basePath, 0777);

        if ($currentUserId !== 0) {
            self::markTestInComplete(
                __METHOD__
                . ' could only run partially, chown() can only to be tested as root user. Current user: '
                . $currentUserName
            );
        }

        // Test user ownership as integer
        $ownership = 10001;
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals($ownership, \fileowner($testFile), 'Expected file owner to be changed.');
        self::assertEquals($currentGroupId, \filegroup($testFile), 'Expected file group to be unchanged.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user ownership as numeric string (should be treated as integer)
        $ownership = '10002';
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals((int)$ownership, \fileowner($testFile), 'Expected created test file owner to be changed.');
        self::assertEquals($currentGroupId, \filegroup($testFile), 'Expected file group to be unchanged.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user ownership as string
        $ownership = $currentUserName;
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals(
            $ownership,
            \posix_getpwuid(\fileowner($testFile))['name'],
            'Expected created test file owner to be changed.'
        );
        self::assertEquals($currentGroupId, \filegroup($testFile), 'Expected file group to be unchanged.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user ownership as numeric string with trailing colon (should be treated as integer)
        $ownership = '10003:';
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals((int)$ownership, \fileowner($testFile), 'Expected created test file owner to be changed.');
        self::assertEquals($currentGroupId, \filegroup($testFile), 'Expected file group to be unchanged.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user ownership as string with trailing colon
        $ownership = $currentUserName . ':';
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals(
            \substr($ownership, 0, -1),
            \posix_getpwuid(\fileowner($testFile))['name'],
            'Expected created test file owner to be changed.'
        );
        self::assertEquals($currentGroupId, \filegroup($testFile), 'Expected file group to be unchanged.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user ownership as indexed array (integer value)
        $ownership = [10004];
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals($ownership[0], \fileowner($testFile), 'Expected created test file owner to be changed.');
        self::assertEquals($currentGroupId, \filegroup($testFile), 'Expected file group to be unchanged.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user ownership as indexed array (numeric string value)
        $ownership = ['10005'];
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals((int)$ownership[0], \fileowner($testFile), 'Expected created test file owner to be changed.');
        self::assertEquals($currentGroupId, \filegroup($testFile), 'Expected file group to be unchanged.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user ownership as associative array (string value)
        $ownership = ['user' => $currentUserName];
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals(
            $ownership['user'],
            \posix_getpwuid(\fileowner($testFile))['name'],
            'Expected created test file owner to be changed.'
        );
        self::assertEquals($currentGroupId, \filegroup($testFile), 'Expected file group to be unchanged.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test group ownership as numeric string
        $ownership = ':10006';
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals($currentUserId, \fileowner($testFile), 'Expected file owner to be unchanged.');
        self::assertEquals(
            (int)\substr($ownership, 1),
            \filegroup($testFile),
            'Expected created test file group to be changed.'
        );
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test group ownership as string
        $ownership = ':' . $currentGroupName;
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals($currentUserId, \fileowner($testFile), 'Expected file owner to be unchanged.');
        self::assertEquals(
            \substr($ownership, 1),
            \posix_getgrgid(\filegroup($testFile))['name'],
            'Expected created test file group to be changed.'
        );
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test group ownership as associative array (integer value)
        $ownership = ['group' => 10007];
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals($currentUserId, \fileowner($testFile), 'Expected file owner to be unchanged.');
        self::assertEquals($ownership['group'], \filegroup($testFile), 'Expected created test file group to be changed.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test group ownership as associative array (numeric string value)
        $ownership = ['group' => '10008'];
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals($currentUserId, \fileowner($testFile), 'Expected file owner to be unchanged.');
        self::assertEquals(
            (int)$ownership['group'],
            \filegroup($testFile),
            'Expected created test file group to be changed.'
        );
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test group ownership as associative array (string value)
        $ownership = ['group' => $currentGroupName];
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals($currentUserId, \fileowner($testFile), 'Expected file owner to be unchanged.');
        self::assertEquals(
            $ownership['group'],
            \posix_getgrgid(\filegroup($testFile))['name'],
            'Expected created test file group to be changed.'
        );
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user and group ownership as numeric string
        $ownership = '10009:10010';
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals(
            (int)\explode(':', $ownership)[0],
            \fileowner($testFile),
            'Expected file owner to be changed.'
        );
        self::assertEquals(
            (int)\explode(':', $ownership)[1],
            \filegroup($testFile),
            'Expected created test file group to be changed.'
        );
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user and group ownership as string
        $ownership = $currentUserName . ':' . $currentGroupName;
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals(
            \explode(':', $ownership)[0],
            \posix_getpwuid(\fileowner($testFile))['name'],
            'Expected file owner to be changed.'
        );
        self::assertEquals(
            \explode(':', $ownership)[1],
            \posix_getgrgid(\filegroup($testFile))['name'],
            'Expected created test file group to be changed.'
        );
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user and group ownership as indexed array (integer values)
        $ownership = [10011, 10012];
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals($ownership[0], \fileowner($testFile), 'Expected file owner to be changed.');
        self::assertEquals($ownership[1], \filegroup($testFile), 'Expected created test file group to be changed.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user and group ownership as indexed array (numeric string values)
        $ownership = ['10013', '10014'];
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals((int)$ownership[0], \fileowner($testFile), 'Expected file owner to be changed.');
        self::assertEquals((int)$ownership[1], \filegroup($testFile), 'Expected created test file group to be changed.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user and group ownership as indexed array (string values)
        $ownership = [$currentUserName, $currentGroupName];
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals(
            $ownership[0],
            \posix_getpwuid(\fileowner($testFile))['name'],
            'Expected file owner to be changed.'
        );
        self::assertEquals(
            $ownership[1],
            \posix_getgrgid(\filegroup($testFile))['name'],
            'Expected created test file group to be changed.'
        );
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user and group ownership as associative array (integer values)
        $ownership = ['group' => 10015, 'user' => 10016]; // user/group reversed on purpose
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        clearstatcache(true, $testFile);
        self::assertEquals($ownership['user'], \fileowner($testFile), 'Expected file owner to be changed.');
        self::assertEquals($ownership['group'], \filegroup($testFile), 'Expected created test file group to be changed.');
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user and group ownership as associative array (numeric string values)
        $ownership = ['group' => '10017', 'user' => '10018']; // user/group reversed on purpose
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals((int)$ownership['user'], \fileowner($testFile), 'Expected file owner to be changed.');
        self::assertEquals(
            (int)$ownership['group'],
            \filegroup($testFile),
            'Expected created test file group to be changed.'
        );
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user and group ownership as associative array (string values)
        $ownership = ['group' => $currentGroupName, 'user' => $currentUserName]; // user/group reversed on purpose
        FallbackFileHelper::changeOwnership($testFile, $ownership, null);
        \clearstatcache(true, $testFile);
        self::assertEquals(
            $ownership['user'],
            \posix_getpwuid(\fileowner($testFile))['name'],
            'Expected file owner to be changed.'
        );
        self::assertEquals(
            $ownership['group'],
            \posix_getgrgid(\filegroup($testFile))['name'],
            'Expected created test file group to be changed.'
        );
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected file mode to be unchanged.'
        );

        // Test user ownership as integer with file mode
        $ownership = '10019:10020';
        $fileMode = 0774;
        FallbackFileHelper::changeOwnership($testFile, $ownership, $fileMode);
        \clearstatcache(true, $testFile);
        self::assertEquals(
            \explode(':', $ownership)[0],
            \fileowner($testFile),
            'Expected created test file owner to be changed.'
        );
        self::assertEquals(
            \explode(':', $ownership)[1],
            \filegroup($testFile),
            'Expected file group to be unchanged.'
        );
        self::assertEquals(
            '0' . \decoct($fileMode),
            \substr(\decoct(\fileperms($testFile)), -4),
            'Expected created test file mode to be changed.'
        );

        \chmod($this->basePath, 0777);
    }

    /**
     * @test
     */
    public function shouldNotChangeOwnershipToNonExistingUser(): void
    {
        $dirName = 'change_ownership_non_existing_user';
        $fileName = 'file_1.txt';
        $testFile = $this->basePath . DIRECTORY_SEPARATOR . $dirName . DIRECTORY_SEPARATOR . $fileName;

        $this->createFileStructure(
            [
               $dirName => [
                   $fileName => 'test 1',
               ],
           ]
        );

        // Test user ownership as integer with file mode (Due to the nature of chown we can't use PHPUnit's `expectException`)
        $ownership = 'non_existing_user';
        try {
            FallbackFileHelper::changeOwnership($testFile, $ownership, null);
            throw new \Exception('FallbackFileHelper::changeOwnership() should have thrown error for non existing user.');
        } catch(\Exception $e) {
            self::assertEquals('chown(): Unable to find uid for non_existing_user', $e->getMessage());
        }
    }

    public function providerForInvalidArguments(): array
    {
        return [
            [new \stdClass()],
            [['user' => new \stdClass()]],
            [['group' => new \stdClass()]],
        ];
    }

    /**
     * @test
     * @dataProvider providerForInvalidArguments
     * @param mixed $ownership
     */
    public function shouldThrowInvalidArgumentException($ownership): void
    {
        $dirName = 'change_ownership_invalid_arguments';
        $fileName = 'file_1.txt';
        $file = $this->basePath . DIRECTORY_SEPARATOR . $dirName . DIRECTORY_SEPARATOR . $fileName;

        $this->createFileStructure(
            [
                $dirName => [
                    $fileName => 'test 1',
                ],
            ]
        );

        $this->expectException(InvalidArgumentException::class);
        FallbackFileHelper::changeOwnership($file, $ownership, null);
    }
}
