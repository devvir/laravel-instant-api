<?php

namespace Devvir\InstantApi\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class Discover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instantapi:discover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an InstantApi with automatic discovery of Models for prototyping';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // TODO : Include Sanctum as a dependency? Call its install:api command first?
        // TODO : append to api.php if it exists
        // TODO : add a Create command (discover models but add them to api.php with createInstantApi([...]))
        // TODO : Have a base command, since a lot of functionality will be shared between this and the Create command
        // TODO : Prompt to create policies and requests?
        // TODO : Prompt to create models (with the migrations, policies, resources)?
        // TODO : Prompt to create auth endpoints? (use Fortify instead of just Sanctum)
        $path = base_path('routes/api.php');

        $contents = <<<EOF
<?php

use Devvir\InstantApi\InstantApi;

createInstantApi(InstantApi::discover());

EOF;

        file_put_contents($path, $contents);

        $this->uncommentApiRoutesFile();

        return self::SUCCESS;
    }

    /**
     * Uncomment the API routes file in the application bootstrap file.
     *
     * @return void
     */
    protected function uncommentApiRoutesFile()
    {
        $appBootstrapPath = $this->laravel->bootstrapPath('app.php');

        $content = file_get_contents($appBootstrapPath);

        if (str_contains($content, '// api: ')) {
            (new Filesystem)->replaceInFile(
                '// api: ',
                'api: ',
                $appBootstrapPath,
            );
        } elseif (str_contains($content, 'web: __DIR__.\'/../routes/web.php\',')) {
            (new Filesystem)->replaceInFile(
                'web: __DIR__.\'/../routes/web.php\',',
                'web: __DIR__.\'/../routes/web.php\','.PHP_EOL.'        api: __DIR__.\'/../routes/api.php\',',
                $appBootstrapPath,
            );
        } else {
            $this->components->warn("Unable to automatically add API route definition to [{$appBootstrapPath}]. API route file should be registered manually.");

            return;
        }
    }
}
