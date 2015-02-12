<?php
/*
 * Copyright 2015 Weswit Srl
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace lightstreamer\remote;

interface IMetaDataProvider
{

    public function getAllowedBufferSize($user, $item);

    public function getAllowedMaxBandwidth($user);

    public function getAllowedMaxItemFrequency($user, $item);

    public function getDistinctSnapshotLength($item);

    public function getItems($user, $sessionID, $group);

    public function getMinSourceFrequency($item);

    public function getSchema($user, $sessionID, $group, $schema);

    public function init($params);

    public function isModeAllowed($user, $item, $mode);

    public function modeMayBeAllowed($item, $mode);

    public function notifyNewSession($user, $sessionID, $clientContext);

    public function notifyNewTables($user, $sessionID, $tables);

    public function notifySessionClose($sessionID);

    public function notifyTablesClose($sessionID, $tables);

    public function notifyUser($user, $password, $httpHeaders, $clientPrincipal);

    public function notifyUserMessage($user, $sessionID, $message);

    public function wantsTablesNotification($user);
    
    public function notifyMpnDeviceAccess($user, MpnDeviceInfo $device);
    // legato a SubscriptionDelay
    
    /**
     * Called by Lightstreamer Kernel to check that a User is enabled
     * to activate a Push Notification subscription.
     * If the check succeeds, this also notifies the Metadata Adapter that
     * Push Notifications are being activated.
     * <BR>
     * <BR>Take particular precautions when authorizing subscriptions, if
     * possible check for validity the trigger expression reported by
     * {@link MpnSubscriptionInfo#getTrigger}, as it may contain maliciously
     * crafted code. The MPN notifiers configuration file contains a first-line
     * validation mechanism based on regular expression that may also be used
     * for this purpose.
     * <BR>
     * <BR>The method should perform fast. Any complex data gathering
     * operation (like a check on the overall number of Push Notifications
     * activated) should have been already performed asynchronously.
     * See the notes for {@link #notifyMpnDeviceAccess} for details.
     *
     * @moderato_edition_note Push Notifications are not supported in Moderato edition.
     *
     * @param user A User name.
     * @param sessionID The ID of a Session owned by the User. The session ID is
     * provided for a thorough validation of the Table informations, but Push
     * Notification subscriptions are persistent and survive the session. Thus,
     * any association between this Session ID and this Push Notification
     * subscription should be considered temporary.
     * @param table A TableInfo instance, containing the details of a Table
     * (i.e.: Subscription) for which Push Notification have to be activated.
     * @param mpnSubscription An MpnSubscriptionInfo instance, containing the
     * details of a Push Notification to be activated. Platform specific
     * details may be accessed by casting the class to the platform's specific
     * subclass (i.e. MpnApnsSubscriptionInfo, etc.).
     * @throws CreditsException if the User is not allowed to activate the
     * specified Push Notification in the Session.
     * @throws NotificationException if something is wrong in the parameters,
     * such as inconsistent information about a Table (i.e.: Subscription) or
     * a Push Notification.
     */
    public function notifyMpnSubscriptionActivation($user, $sessionID, TableInfo $table, MpnSubscriptionInfo $mpnSubscription);
    
    /**
     * Called by Lightstreamer Kernel to check that a User is enabled to change
     * the token of a MPN device.
     * If the check succeeds, this also notifies the Metadata Adapter that future
     * client requests should be issued by specifying the new device token.
     * <BR>
     * <BR>Take particular precautions when authorizing device token changes,
     * if possible ensure the user is entitled to the new device token.
     * <BR>
     * <BR>The method should perform fast. Any complex data gathering
     * operation (like a check on the devices currently served) should have been
     * already performed asynchronously.
     * See the notes for {@link #notifyMpnDeviceAccess} for details.
     *
     * @moderato_edition_note Push Notifications are not supported in Moderato edition.
     *
     * @param user A User name.
     * @param device specifies a MPN device.
     * @param newDeviceToken The new token being assigned to the device.
     * @throws CreditsException if the User is not allowed to change the
     * specified device token.
     * @throws NotificationException if something is wrong in the parameters,
     * such as inconsistent information about the device.
     */
    public function notifyMpnDeviceTokenChange($user, MpnDeviceInfo $device, $newDeviceToken);
}
?>