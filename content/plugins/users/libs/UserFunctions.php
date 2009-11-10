<?php
/**
 * The UserFunctions class contains some useful methods when using a UserFunctions
 *
 * PHP version 5
 *
 * LICENSE: Hotaru CMS is free software: you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License as 
 * published by the Free Software Foundation, either version 3 of 
 * the License, or (at your option) any later version. 
 *
 * Hotaru CMS is distributed in the hope that it will be useful, but WITHOUT 
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or 
 * FITNESS FOR A PARTICULAR PURPOSE. 
 *
 * You should have received a copy of the GNU General Public License along 
 * with Hotaru CMS. If not, see http://www.gnu.org/licenses/.
 * 
 * @category  Content Management System
 * @package   HotaruCMS
 * @author    Nick Ramsay <admin@hotarucms.org>
 * @copyright Copyright (c) 2009, Hotaru CMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link      http://www.hotarucms.org/
 */
    
class UserFunctions extends UserBase
{    
    /**
     * Get all users with permission to access admin
     */
    public function getMods($permission = 'can_access_admin', $value = 'yes')
    {
        $sql = "SELECT * FROM " . TABLE_USERS . " WHERE (user_role = %s) || (user_role = %s) || (user_role = %s)";
        $users = $this->db->get_results($this->db->prepare($sql, 'admin', 'supermod', 'moderator'));
        
        if (!$users) { return false; }
        
        foreach ($users as $user) {
            $this->getUserBasic($user->user_id);
            if ($this->getPermission($permission) == $value) {
                $admins[$this->id]['id'] = $this->id;
                $admins[$this->id]['role'] = $this->role;
                $admins[$this->id]['name'] = $this->name;
                $admins[$this->id]['email'] = $this->email;
            }
        }
        return $admins;
    }


    /**
     * Send an email to admins/supermods chosen to receive emails about new user signups
     *
     * @param string $type - notification type, e.g. 'post', 'user', 'comment'
     * @param string $status - role or status new user, post or comment
     * @param string $id - post or user id
     * @param string $commentid - comment id
     */
    public function notifyMods($type, $status, $id = 0, $commentid = 0)
    {
        $line_break = "\r\n\r\n";
        $next_line = "\r\n";
        
        // build email ($url is the link admins can click to go to to approve the user/post/comment)
        switch ($type) {
            case 'user':
                $users_settings = $this->plugins->getSerializedSettings('users');
                $email_mods = $users_settings['users_email_notify_mods'];
                $subject = $this->lang['userfunctions_notifymods_subject_user'];
                $about = $this->lang['userfunctions_notifymods_body_about_user'];
                $url = BASEURL . "admin_index.php?page=plugin_settings&plugin=user_manager&page=plugin_settings&type=filter&user_filter=pending";
                break;
            case 'post':
                $submit_settings = $this->plugins->getSerializedSettings('submit');
                $email_mods = $submit_settings['post_email_notify_mods'];
                $subject = $this->lang['userfunctions_notifymods_subject_post'];
                $about = $this->lang['userfunctions_notifymods_body_about_post'];
                $url = BASEURL . "index.php?page=edit_post&post_id=" . $id;
                break;
            case 'comment':
                $comments_settings = $this->plugins->getSerializedSettings('comments');
                $email_mods = $comments_settings['comment_email_notify_mods'];
                $subject = $this->lang['userfunctions_notifymods_subject_comment'];
                $about = $this->lang['userfunctions_notifymods_body_about_comment'];
                $url = BASEURL . "admin_index.php?page=plugin_settings&plugin=comment_manager&page=plugin_settings&type=filter&comment_status_filter=pending";
                break;
            default:
        }
        
        // send email

        foreach ($email_mods as $mod)
        {
            if ($mod['type'] == 'none') { continue; } // skip rest of this iteration
            if (($mod['type'] == 'pending') && ($status != 'pending')) { continue; } // skip rest of this iteration
            
            $body = $this->lang['userfunctions_notifymods_hello'] . $this->getUserNameFromId($mod['id']);
            $body .= $line_break;
            $body .= $about;
            $body .= $line_break;
            $body .= $this->lang['userfunctions_notifymods_body_click'];
            $body .= $line_break;
            $body .= $url;
            $body .= $line_break;
            $body .= $this->lang['userfunctions_notifymods_body_regards'];
            $body .= $next_line;
            $body .= $this->lang['userfunctions_notifymods_body_sign'];
            $to = $mod['email'];
            $headers = "From: " . SITE_EMAIL . "\r\nReply-To: " . SITE_EMAIL . "\r\nX-Priority: 3\r\n";
        
            mail($to, $subject, $body, $headers);    
        }
        
        return true;
    }
}

?>