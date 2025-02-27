<?php

declare(strict_types=1);

namespace Liquetsoft\Fias\Component\Pipeline\Task;

use Exception;
use Liquetsoft\Fias\Component\Pipeline\State\State;

/**
 * Интерфейс для объекта, который производит одну атомарную операцию,
 * необходимую для загрузки данных ФИАС из файлов в базу данных.
 */
interface Task
{
    public const FIAS_VERSION_PARAM = 'fias_version';

    public const FIAS_INFO_PARAM = 'fias_info';

    public const DOWNLOAD_TO_FILE_PARAM = 'download_to';

    public const EXTRACT_TO_FOLDER_PARAM = 'extract_to';

    public const FILES_TO_PROCEED = 'files_to_proceed';

    /**
     * Запускает задачу на исполнение.
     *
     * @param State $state
     *
     * @throws Exception
     */
    public function run(State $state): void;
}
