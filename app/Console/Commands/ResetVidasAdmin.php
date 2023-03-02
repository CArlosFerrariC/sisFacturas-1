<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetVidasAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:vidas.admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba del reseteo de vidas del admin, se ejecutara una sola vez';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
      $this->warn("Comando : Reseteo vidas");
      $usuarios=User::where('id',1)->get();
      $progress = $this->output->createProgressBar($usuarios->count());
      foreach($usuarios as $user)
      {
        $this->warn($user->id);
        $user->update([
          'vidas_total' => 3,
          'vidas_restantes' => 3,
          'cant_vidas_cero' => 0,
        ]);
        $progress->advance();
      }
      $this->info("Finalizando...");
      $progress->finish();
      $this->info('FIN');
    }
}
