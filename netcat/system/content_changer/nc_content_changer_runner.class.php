<?php

class nc_content_changer_runner {

    /**
     * @var nc_content_changer_instruction[]
     */
    private $instructions;


    /**
     * @param array $instructions
     */
    public function __construct(array $instructions) {
        $this->instructions = $instructions;
    }

    /**
     * @return void
     */
    public function run() {
        foreach ($this->get_instructions() as $instruction) {
            $content_change_class = "nc_content_changer_" . $instruction->get_entity_type();

            if (!class_exists($content_change_class)) {
                continue;
            }

            /** @var nc_content_changer $content_change_class */
            $content_changer = new $content_change_class($instruction);

            try {
                $content_changer->apply();
            } catch (Error $e) {
                $message = "[Instruction execution error]: \r\nInstruction: " . $instruction->to_json() . ", \r\nError: " . $e->getMessage();
                trigger_error($message, E_USER_WARNING);
                continue;
            } catch (Exception $e) {
                $message = "[Instruction execution error]: Instruction: " . $instruction->to_json() . ", Error: " . $e->getMessage();
                trigger_error($message, E_USER_WARNING);
                continue;
            }
        }
    }

    /**
     * @return nc_content_changer_instruction[]
     */
    public function get_instructions() {
        return $this->instructions;
    }
}
