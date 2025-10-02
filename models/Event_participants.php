<?php 
    class Event_participants extends Model {
        private $id;
        private $participant_id;
        private $event_id;
        private $check_in;
        private $check_out;

        protected static $table = 'event_participants';
        protected static $requiredFields = ['participant_id', 'event_id'];
        protected static $uniqueFields = [];


        public static function validateData($data) {
            $errors = static::checkRequiredFields($data);

            return $errors ?: static::checkDataUniqueness($data);
        }

        public static function prepareData($data, $new = false) {
            return $data;
        }
    }
?>