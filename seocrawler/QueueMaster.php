<?php

apf_require_class('Seocrawler_Candidate');
apf_require_class('Seocrawler_Queue');

class Seocrawler_QueueMaster
{

    public function run()
    {
        $total_sleep = 0;

        while (true) {
            $sleep = 2;

            // get candidate id
            $num = 500;
            $ids = Seocrawler_Candidate::get_instance()->getCandidateIds($num);

            // insert into queue
            if ($ids) {
                foreach($ids as $id) {
                    Seocrawler_Queue::getInstance()->push($id['site_id'], $id['batch_id'], $id['id']);
                }

                if (count($ids) == $num) {
                    $sleep = 1;
                }

                print date('c ') . " put " . count($ids) . " into queue\n";
            }

            $total_sleep += $sleep;

            if ($sleep) {
                sleep($sleep);
            }

            /*if ($total_sleep >= 600 ) {
                break;
            }*/

            print date('c ') . " total sleep: $total_sleep \n";
        }
    }
}
