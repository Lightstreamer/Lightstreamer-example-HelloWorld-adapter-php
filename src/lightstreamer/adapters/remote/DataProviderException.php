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
 * Thrown by the init method in DataProvider if there is some problem that prevents the correct behavior
 * of the Data Adapter. If this exception occurs, Lightstreamer Kernel must give up the startup.
 */
class DataProviderException extends DataException {

    /**
     * Constructs a DataProviderException with a supplied error message text.
     *
     * @param message The detail message.
     */
    public function __construct($message) {
        parent::__construct($message);
    }
}