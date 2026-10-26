<?php

/** Minimal EE fieldtype state used by the archive's base class. */
class EE_Fieldtype
{
    public $settings = array();
    public $entryId = 0;

    /**
     * Return the current content model identifier.
     *
     * @return int
     */
    public function content_id()
    {
        return $this->entryId;
    }
}
