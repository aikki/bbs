<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class Run extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'run {--d|draw} {--l|draw-lines=190}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $p = 99371; //7
        $q = 102203; //11
        $z = 20000; //ile generować

        $this->info("Podane liczby: p = $p; q = $q");

        if (gmp_prob_prime($p) <= 0) {
            $this->error("$p nie jest liczbą pierwszą!");
            return;
        } else {
            $this->comment("$p jest liczbą pierwszą");
        }

        $mod = $p % 4;
        if ($mod !== 3) {
            $this->error("$p mod 4 = $mod != 3");
            return;
        }
        $this->comment("$p mod 4 = $mod == 3");

        if (gmp_prob_prime($q) <= 0) {
            $this->error("$q nie jest liczbą pierwszą!");
            return;
        } else {
            $this->comment("$q jest liczbą pierwszą");
        }

        $mod = $q % 4;
        if ($mod !== 3) {
            $this->error("$q mod 4 = $mod != 3");
            return;
        }
        $this->comment("$q mod 4 = $mod == 3");

        $n = $p*$q;

        $this->info("Liczba n = $p * $q = $n");

        do {
            $s = rand(1, $n-1);
        } while (gmp_gcd($s, $n) != 1);

        $this->info("Liczba s = $s");
        $this->comment("NWD($s, $n) = " . gmp_strval(gmp_gcd($s, $n)));

        $array = [];

        $arrayb = [];

        // $array[0] = $this->truemod(pow($s, 2), $n);
        $array[0] = pow($s, 2) % $n;
        $arrayb[0] = $array[0] & 1;

        for ($i = 1; $i < $z; $i++) {
            // $array[$i] = $this->truemod(pow($array[$i-1], 2), $n);
            $array[$i] = pow($array[$i-1], 2) % $n;
            $arrayb[$i] = $array[$i] & 1;
        }
        
        
        if ($this->option('draw')) {
            $line = 0;
            foreach ($arrayb as $a) {
                echo $a ? '█' : ' ';
                if (++$line % $this->option('draw-lines') === 0) $this->newLine();
            }
            $this->newLine();
        }
        
        $this->info('Testy statystyczne FIPS 140-2: ');

        $n1 = 0;
        $seria = 0;
        $serArr = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
        $maxSeria = 0;

        $poker = '';
        $pokerArr = [];
        for ($i = 0; $i < 16; $i++) {
            $pokerArr[$i] = 0;
        }

        $bar = $this->output->createProgressBar(count($arrayb));
        $bar->start();

        foreach ($arrayb as $a) {
            //Test pojedynczych bitów
            $n1 += $a;

            //Testy serii
            if ($a === 1) {
                $seria++;
            } elseif ($seria > 0) {
                $x = $seria <= 5 ? $seria : 6;
                $serArr[$x]++;
                if ($seria > $maxSeria) $maxSeria = $seria;
                $seria = 0;
            }

            //Test pokerowy
            $poker .= (string)$a;
            if (strlen($poker) === 4) {
                $pokerArr[bindec($poker)]++;
                $poker = '';
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->comment('  Test pojedynczych bitów:');
        $this->showBetween($n1, 9725, 10275, 'n(1)');

        $this->comment('  Test serii:');
        $this->showBetween($serArr[1], 2315, 2685, 's(1)');
        $this->showBetween($serArr[2], 1114, 1386, 's(2)');
        $this->showBetween($serArr[3], 527, 723, 's(3)');
        $this->showBetween($serArr[4], 240, 384, 's(4)');
        $this->showBetween($serArr[5], 103, 209, 's(5)');
        $this->showBetween($serArr[6], 103, 209, 's(6+)');

        $this->comment('  Test długiej serii:');
        if ($maxSeria < 26) {
            $this->info("    $maxSeria < 26");
        } else {
            $this->error("    $maxSeria >= 26");
        }

        $this->comment('  Test pokerowy:');
        $pokerVal = 0;
        foreach ($pokerArr as $pa) {
            $pokerVal += pow($pa, 2);
        }
        $pokerVal *= 16/5000;
        $pokerVal -= 5000;
        $this->showBetween($pokerVal, 2.16, 46.17, 'X');
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }

    private function truemod($num, $mod) {
        return ($mod + ($num % $mod)) % $mod;
    }

    private function showBetween($num, $left, $right, $ozn) {
        if ($num < $left) {
            $this->error("    $ozn = $num < $left");
        } elseif ($num > 10275) {
            $this->error("    $ozn = $num > $right");
        } else {
            $this->info("    $left < $ozn = $num < $right");
        }
    }
}
