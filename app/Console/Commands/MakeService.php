<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeService extends Command
{
    protected $files;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service class';

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $path = app_path("Services/{$name}.php");

        if ($this->files->exists($path)) {
            $this->error('Service already exists!');
            return false;
        }

        $this->makeDirectory($path);

        $stub = $this->getStub();
        $this->files->put($path, str_replace('{{class}}', $name, $stub));

        $this->info('Service created successfully.');
    }

    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    protected function getStub()
    {
        return <<<EOT
        <?php

        namespace App\Services;

        class {{class}}
        {
            public function __construct()
            {
                // Initialize service
            }

            // Define service methods
        }
        EOT;
    }
}
