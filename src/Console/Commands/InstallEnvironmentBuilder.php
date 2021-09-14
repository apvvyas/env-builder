<?php 

namespace Appspubs\EnvironmentBuilder\Console\Commands;

use File;
use Dotenv\Dotenv;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallEnvironmentBuilder extends Command
{
     /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env-builder:install';

    protected $basePath  = '';

    protected $envDir = '';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command installs the environment builder packages and publishes the files';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->basePath =  base_path();
    }

    public function handle()
    {
        $this->info('Installing Environment Builder...');

        $this->info('Publishing configuration...');

        if (! $this->configExists('env-builder.php')) {
            $this->publishConfiguration();
            $this->info('Published configuration');
        } else {
            if ($this->shouldOverwriteConfig()) {
                $this->info('Overwriting configuration file...');
                $this->publishConfiguration($force = true);
            } else {
                $this->info('Existing configuration was not overwritten');
            }
        }

        $this->setup();

        $this->info('Installed Environment Builder');
    }

    private function configExists($fileName)
    {
        return File::exists(config_path($fileName));
    }

    private function shouldOverwriteConfig()
    {
        return $this->confirm(
            'Config file already exists. Do you want to overwrite it?',
            false
        );
    }

    private function publishConfiguration($forcePublish = false)
    {
        $params = [
            '--provider' => "Appspubs\EnvBuilder\Providers\EnvBuildServiceProvider",
            '--tag' => "config"
        ];

        if ($forcePublish === true) {
            $params['--force'] = true;
        }

       $this->call('vendor:publish', $params);
       $this->call('optimize');
    }

    private function setup(){
        $this->envDir = implode(DIRECTORY_SEPARATOR, [
            $this->basePath, config('env-builder.folder')
        ]);

        $this->info('Following files will be modified : ');
        $this->table(
            ['File'],
            [
                implode(DIRECTORY_SEPERATOR, [ 
                    $this->basePath, 'bootstrap','app.php'
                ]),
                implode(DIRECTORY_SEPERATOR, [ 
                    $this->basePath, '.env'
                ])
            ]);

        if($this->confirm(
            'Proceeding Further will modify the files. Do you want to go ahead?',
            false
        )){

            $this->info('Creating directory : '.$this->envDir);
            if(!File::isDirectory($this->envDir)){
                File::makeDirectory($this->envDir);
            }

            $this->info('Creating File : '.implode(DIRECTORY_SEPERATOR, [ 
                $this->basePath, 'bootstrap','environment.php'
            ]));

            $this->createBootEnvStub();


            $this->info('Updating File : '.implode(DIRECTORY_SEPERATOR, [ 
                $this->basePath, 'bootstrap','app.php'
            ]));

            $this->updateBootstrapApp();

            $this->generateLocalEnvironment();
        }
        else{
            $this->info('Please update these files accordingly for the app to have the environment setup');
        }

    }

    private function createBootEnv()
    {
        $data = File::get(implode(DIRECTORY_SEPERATOR, [
            __DIR__, '..', 'Stubs', 'BoostrapEnvironment.stub'
        ]));

        $data = Str::replace('{{env_folder}}', config('env-builder.folder'), $data);

        File::put(implode(DIRECTORY_SEPERATOR, [ 
            $this->basePath, 'bootstrap','environment.php'
        ]), $data);

        $this->info('File created successfully: '.$this->envDir);
    }

    private function updateBootstrapApp(){
        $data = File::get(implode(DIRECTORY_SEPERATOR, [
            __DIR__, '..', 'Stubs', 'BoostrapApp.stub'
        ]));

        File::put(implode(DIRECTORY_SEPERATOR, [ 
            $this->basePath, 'bootstrap','app.php'
        ]), $data);

        $this->info('File updated successfully: '.implode(DIRECTORY_SEPERATOR, [ 
            $this->basePath, 'bootstrap','app.php'
        ]));
    }

    private function generateLocalEnvironment(){
        $this->call('generate:environment',[
            'name' => 'local', 
            'from' => '.env'
        ]);

        $this->call('generate:environment', [
            'name' => 'local',
            '--set-env' => 'true'
        ]);
    }
}