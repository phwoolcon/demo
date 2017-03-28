<?php

namespace Phwoolcon\Cli\Command;

use Exception;
use Phwoolcon\Config;
use Phwoolcon\Db;
use Phwoolcon\Log;

class MigrateRevert extends Migrate
{

    protected function configure()
    {
        $this->setDescription('Revert last migration.')
            ->setAliases(['migrate:down']);
    }

    public function fire()
    {
        $this->checkMigrationsTable();
        if ($lastMigration = $this->getLastMigration()) {
            $file = $lastMigration['file'];
            $runAt = $lastMigration['run_at'];
            $this->comment(sprintf(' You are going to revert migration "%s" which was run at %s', $file, $runAt));
            if (Config::runningUnitTest() || $this->confirm('please confirm', false)) {
                $this->revertMigration($file);
            }
        } // @codeCoverageIgnoreStart
        else {
            $this->info('No migrations to be reverted.');
        }
        // @codeCoverageIgnoreEnd
    }

    protected function getLastMigration()
    {
        $db = $this->db;
        isset($this->sql[$sqlKey = 'get_last_migration']) or $this->sql[$sqlKey] =
            strtr('SELECT * FROM `table` ORDER BY `run_at` DESC LIMIT 1', [
                '`table`' => $db->escapeIdentifier($this->table),
                '`run_at`' => $db->escapeIdentifier('run_at'),
            ]);
        return $db->fetchOne($this->sql[$sqlKey]);
    }

    protected function revertMigration($filename)
    {
        $db = $this->db;
        $db->begin();
        $file = migrationPath($filename);
        try {
            $this->logAndShowInfo(sprintf('Start reverting migration "%s"', $filename));
            $migration = include $file;
            if (isset($migration['down']) && is_callable($migration['down'])) {
                call_user_func($migration['down'], $db, $this);
            }
            $this->migrationExecuted($filename, false);
            $db->commit();
            Db::clearMetadata();
            $this->logAndShowInfo(sprintf('Finish reverting migration "%s"', $filename));
        } // @codeCoverageIgnoreStart
        catch (Exception $e) {
            $db->rollback();
            Log::exception($e);
            $this->error(sprintf('Error when reverting migration "%s"', $filename));
            $this->error($e->getMessage());
        }
        // @codeCoverageIgnoreEnd
    }
}
