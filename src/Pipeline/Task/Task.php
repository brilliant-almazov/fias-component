<?php

declare(strict_types=1);

namespace Liquetsoft\Fias\Component\Pipeline\Task;

use Liquetsoft\Fias\Component\Pipeline\State\State;
use Exception;

/**
 * Интерфейс для объекта, который производит одну атомарную операцию,
 * необходимую для загрузки данных ФИАС из файлов в базу данных.
 */
interface Task
{
    const FIAS_VERSION_PARAM = 'fias_version';

    const FIAS_INFO_PARAM = 'fias_info';

    const DOWNLOAD_TO_FILE_PARAM = 'download_to';

    const EXTRACT_TO_FOLDER_PARAM = 'extract_to';

    /**
     * Запускает задачу на исполнение.
     *
     * @param State $state
     *
     * @throws Exception
     */
    public function run(State $state): void;
}
