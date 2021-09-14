<?php

namespace Appspubs\EnvironmentBuilder\Console\Commands;

use File;
use Dotenv\Dotenv;
use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Artisan;

class GenerateEnvironment extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:environment {name : Name of the environment} {from?} {--set-env= :True or false}';

    protected $basePath  = '';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Environment File from with values';


    private $dotEnv = [];

    private $dotEnvDefaults = '';

    private $environPath = '';

    private $currentPath = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->basePath =  base_path();
        $this->currentPath = __DIR__;
    }

    protected function getStub()
    {
        return __DIR__ . '/Stubs/Env.stub';
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $setEnv = $this->option('set-env');

        $this->environPath = implode(DIRECTORY_SEPARATOR, [
            $this->basePath, config('env-builder.folder')
        ]);

        if(Str::is(Str::lower($setEnv), 'true'))
            $this->setEnv();
        else
            $this->createEnv();
    }

    private function setEnv(){
        $mainEnvironPath = implode(DIRECTORY_SEPARATOR, [
            $this->basePath, '.env'
        ]);

        File::put($mainEnvironPath, $this->argument('name'));

        $this->info("Environment ".$this->argument('name'). " set successfully. ");
    }

    private function createEnv(){
        $this->getDefaultDetails();
        $this->getDetails();

        $environment = $this->argument('name');

        $this->setAppEnv($environment);

        if(!File::isDirectory($this->environPath))
            File::makeDirectory($this->environPath, 0644, true, true);

        $envFile = implode(DIRECTORY_SEPARATOR, [
            $environPath, '.'.$environment.'.env'
        ]);

        File::put($envFile, $this->getVariableString());

        $this->info("Environment $environment Created Successfully with file : ". $envFile);
    }

    private function getDefaultDetails(){

        $file = '.env';

        if(!empty($this->argument('from')))
            $file = $this->argument('from');
        else{
            $file = implode(DIRECTORY_SEPERATOR, [ config('env-builder.folder'), $file ] );
        }

        $file = implode(DIRECTORY_SEPARATOR, [
            $this->basePath,$file
        ]);

        if(File::isDirectory($this->environPath))
            $file = config('env-builder.default_env_data'); 

        $this->dotEnv = collect(DotEnv::parse(File::get($file)));
    }

    private function getDetails(){
        
        $this->dotEnvDefaults = File::get($this->getStub());
    }

    private function getVariableString(){

        $defaults = $this->dotEnvDefaults;

        $this->dotEnv->each(function($value, $key) use(&$defaults){
            $defaults = Str::replace('{{'.$key.'}}', $value, $defaults);
        });

        return $defaults;
    }

    private function setAppEnv($value){
        $this->dotEnv['APP_ENV'] = $value;
    }
}
