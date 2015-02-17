<?php
/*
 * Copyright (c) 2004-2014 Weswit s.r.l., Via Campanini, 6 - 20124 Milano, Italy.
 * All rights reserved.
 * www.lightstreamer.com
 *
 * This software is the confidential and proprietary information of
 * Weswit s.r.l.
 * You shall not disclose such Confidential Information and shall use it
 * only in accordance with the terms of the license agreement you entered
 * into with Weswit s.r.l.
 */
namespace lightstreamer\adapters\remote;

/**
 * Thrown by the subscribe and unsubscribe methods in DataProvider if the method execution has caused
 * a severe problem that can compromise future operation of the Data Adapter.
 */
class FailureException extends DataException
{

    /**
     * Constructs a FailureException with a supplied error message text.
     *
     * @param
     *            msg The detail message.
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}