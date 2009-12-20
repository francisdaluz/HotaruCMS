<?php
/**
 * name: SB Submit
 * description: Social Bookmarking submit - Enables post submission
 * version: 0.1
 * folder: sb_submit
 * class: SbSubmit
 * type: addpost
 * hooks: install_plugin, theme_index_top, header_include, header_include_raw, navigation, admin_header_include_raw, theme_index_main, admin_plugin_settings, admin_sidebar_plugin_settings
 * requires: sb_base 0.1
 * author: Nick Ramsay
 * authorurl: http://hotarucms.org/member.php?1-Nick
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

class SbSubmit
{
    public $hotaru = '';   // access Hotaru functions using $this->hotaru
    
    /**
     * Install Submit settings if they don't already exist
     */
    public function install_plugin()
    {
        // Permissions
        $site_perms = $this->hotaru->getDefaultPermissions('all');
        if (!isset($site_perms['can_submit'])) { 
            $perms['options']['can_submit'] = array('yes', 'no', 'mod');
            $perms['options']['can_post_without_link'] = array('yes', 'no');
            
            $perms['can_submit']['admin'] = 'yes';
            $perms['can_submit']['supermod'] = 'yes';
            $perms['can_submit']['moderator'] = 'yes';
            $perms['can_submit']['member'] = 'yes';
            $perms['can_submit']['undermod'] = 'mod';
            $perms['can_submit']['default'] = 'no';
            
            $perms['can_post_without_link']['admin'] = 'yes';
            $perms['can_post_without_link']['supermod'] = 'yes';
            $perms['can_post_without_link']['default'] = 'no';
            
            $this->hotaru->updateDefaultPermissions($perms);
        }
        

        // Default settings 
        $submit_settings = $this->hotaru->getSerializedSettings();
        
        //if (!isset($submit_settings['enabled'])) { $submit_settings['enabled'] = "checked"; }
        if (!isset($submit_settings['content'])) { $submit_settings['content'] = "checked"; }
        if (!isset($submit_settings['content_length'])) { $submit_settings['content_length'] = 50; }
        if (!isset($submit_settings['summary'])) { $submit_settings['summary'] = "checked"; }
        if (!isset($submit_settings['summary_length'])) { $submit_settings['summary_length'] = 200; }
        if (!isset($submit_settings['allowable_tags'])) { $submit_settings['allowable_tags'] = "<b><i><u><a><blockquote><strike>"; }
        if (!isset($submit_settings['url_limit'])) { $submit_settings['url_limit'] = 0; }
        if (!isset($submit_settings['daily_limit'])) { $submit_settings['daily_limit'] = 0; }
        if (!isset($submit_settings['freq_limit'])) { $submit_settings['freq_limit'] = 0; }
        if (!isset($submit_settings['set_pending'])) { $submit_settings['set_pending'] = ""; } // sets all new posts to pending 
        if (!isset($submit_settings['x_posts'])) { $submit_settings['x_posts'] = 1; }
        if (!isset($submit_settings['email_notify'])) { $submit_settings['email_notify'] = ""; }
        if (!isset($submit_settings['email_notify_mods'])) { $submit_settings['email_notify_mods'] = array(); }
        
        $this->hotaru->updateSetting('sb_submit_settings', serialize($submit_settings));
    }
    
    
    /**
     * Determine the pageType
     */
    public function theme_index_top()
    {
        // Include SbSubmitFunctions if this is page name contains 'submit' 
        if (strpos($this->hotaru->pageName, 'submit') !== false) {
            include_once(PLUGINS . 'sb_submit/libs/SbSubmitFunctions.php'); // used for submit functions
        }
        
        switch ($this->hotaru->pageName)
        {
            // SUBMIT STEP 1
            case 'submit':
            case 'submit1':
            
                // set properties
                $this->hotaru->pageName = 'submit1';
                $this->hotaru->pageType = 'submit';
                $this->hotaru->pageTitle = $this->hotaru->lang["submit_step1"];
                
                // get functions and check if data has been submitted
                $funcs = new SbSubmitFunctions();
                $submitted = $funcs->checkSubmitted($this->hotaru, 'submit1');
                
                // save/reload data, then go to step 2 when no more errors
                if ($submitted) {
                    $key = $funcs->processSubmitted($this->hotaru, 'submit1');
                    $errors = $funcs->checkErrors($this->hotaru, 'submit1', $key);
                    if (!$errors) {
                        $redirect = htmlspecialchars_decode($this->hotaru->url(array('page'=>'submit2', 'key'=>$key)));
                        header("Location: " . $redirect);
                        exit;
                    }
                }
                break;
                
            // SUBMIT STEP 2 
            case 'submit2':
            
                // set properties
                $this->hotaru->pageType = 'submit';
                $this->hotaru->pageTitle = $this->hotaru->lang["submit_step2"];
                
                // get settings, functions and check if data has been submitted
                $this->hotaru->vars['submit_settings'] = $this->hotaru->getSerializedSettings('sb_submit');
                $funcs = new SbSubmitFunctions();
                $submitted = $funcs->checkSubmitted($this->hotaru, 'submit2');
                
                // not submitted so reload data from step 1 (or step 2 if editing)
                if (!$submitted) {
                    // if coming from step 1, get the key from the url
                    $key = $this->hotaru->cage->get->testAlnum('key');
                    
                    // use the key in the step 2 form
                    $this->hotaru->vars['submit_key'] = $key; 
                    
                    // load submitted data:
                    $submitted_data = $funcs->loadSubmitData($this->hotaru, $key);
                    
                    // merge defaults from "checkSubmitted" with $submitted_data...
                    $merged_data = array_merge($this->hotaru->vars['submitted_data'], $submitted_data);
                    $this->hotaru->vars['submitted_data'] = $merged_data;
                }
                
                // submitted so save data and proceed to step 3 when no more errors
                if ($submitted) {
                    $key = $funcs->processSubmitted($this->hotaru, 'submit2');
                    $errors = $funcs->checkErrors($this->hotaru, 'submit2', $key);
                    if (!$errors) {
                        $funcs->processSubmission($this->hotaru, $key);
                        $postid = $this->hotaru->post->id; // got this from addPost in Post.php
                        $link = $this->hotaru->url(array('page'=>'submit3', 'postid'=>$postid,'key'=>$key));
                        $redirect = htmlspecialchars_decode($link);
                        header("Location: " . $redirect);
                        exit;
                    }
                    $this->hotaru->vars['submit_key'] = $key; // used in the step 2 form
                }
                break;
                
            // SUBMIT STEP 3
            case 'submit3':
            
                $this->hotaru->pageType = 'submit';
                $this->hotaru->pageTitle = $this->hotaru->lang["submit_step3"];
                
                // Check if the Edit button has been clicked
                $funcs = new SbSubmitFunctions();
                $submitted = $funcs->checkSubmitted($this->hotaru, 'submit3');
                
                // Edit button pressed so save data with newly assigned post id and go back to step 2
                if ($submitted) {
                    $key = $funcs->processSubmitted($this->hotaru, 'submit3');
                    $funcs->processSubmission($this->hotaru, $key);
                    $link = $this->hotaru->url(array('page'=>'submit2', 'key'=>$key));
                    $redirect = htmlspecialchars_decode($link);
                    header("Location: " . $redirect);
                    exit;
                }
                
                // get key from the url for the submit 3 form
                $key = $this->hotaru->cage->get->testAlnum('key');
                $this->hotaru->vars['submit_key'] = $key; 
                
                // get post id from the url and read the post for the preview
                $this->hotaru->post->id = $this->hotaru->cage->get->testInt('postid');
                $this->hotaru->readPost();

                break;
                
            // Submit Confirm
            case 'submit_confirm':
            
                $post_id = $this->hotaru->cage->post->testInt('submit_post_id');
                $this->hotaru->readPost($post_id);
                $this->hotaru->changePostStatus('new');
                
                $return = 0; // will return false later if set to 1.
                
                $this->hotaru->pluginHook('submit_step_3_pre_trackback'); // Akismet uses this to change the status
                
                // Get settings
                $submit_settings = $this->hotaru->getSerializedSettings();
                $set_pending = $submit_settings['set_pending'];

                if ($set_pending == 'some_pending') {
                    $posts_approved = $this->hotaru->postsApproved();
                    $x_posts_needed = $submit_settings['x_posts'];
                }

                
                // Set to pending is the user's permissions for "can_submit" are "mod" OR
                // if "Put all new posts in moderation" has been checked in Admin->Submit
                if (   ($this->hotaru->currentUser->getPermission('can_submit') == 'mod')
                    || ($set_pending == 'all_pending')
                    || (($set_pending == 'some_pending') && ($posts_approved <= $x_posts_needed)))
                {
                // Submitted posts given 'pending' for this user
                    $this->hotaru->changePostStatus('pending');
                    $this->hotaru->messages[$this->hotaru->lang['submit_form_moderation']] = 'green';
                    $return = 1; // will return false just after we notify admins of the post (see about 10 lines down)
                }

                // notify chosen mods of new post by email if enabled and UserFunctions file exists
                /*
                if (($submit_settings['email_notify']) && (file_exists(PLUGINS . 'users/libs/UserFunctions.php')))
                {
                    require_once(PLUGINS . 'users/libs/UserFunctions.php');
                    $uf = new UserFunctions($this->hotaru);
                    $uf->notifyMods('post', $this->hotaru->post->status, $this->hotaru->post->id);
                }
                */
                
                if ($return == 1) { return false; } // post is pending so we don't want to send a trackback. Return now.
                
                $this->hotaru->sendTrackback();
                
                header("Location: " . $this->hotaru->url(array('page'=>'latest')));    // Go to the Latest page
                die();
                break;
        }
        
        if ($this->hotaru->pageType != 'submit') { return false; }
        
        // If the user is not logged in...
        if (!$this->hotaru->currentUser->loggedIn) {
            $return = urlencode($this->hotaru->url(array('page'=>'submit'))); // return user here after login
            header("Location: " . $this->hotaru->url(array('page'=>'login', 'return'=>$return)));
        }
    }


    /**
     * Include jQuery for hiding and showing email options in plugin settings
     */
    public function admin_header_include_raw()
    {
        if ($this->hotaru->isSettingsPage('submit')) {
            echo "<script type='text/javascript'>\n";
            echo "$(document).ready(function(){\n";
                echo "$('#email_notify').click(function () {\n";
                echo "$('#email_notify_options').slideToggle();\n";
                echo "});\n";
            echo "});\n";
            echo "</script>\n";
        }
    }
    
    
    /**
     * Output raw javascript directly to the header (instead of caching a .js file)
     */
    public function header_include_raw()
    {
        /* This code (courtesy of Pligg.com and SocialWebCMS.com) pops up a 
           box asking the user of they are sure they want to leave the page
           without submitting their post. */
           
        if ($this->hotaru->pageName == 'submit2' || $this->hotaru->pageName == 'submit3') {
            echo '
                <script type="text/javascript">
        
                var safeExit = false;
            
                window.onbeforeunload = function (event) 
                {
                    if (safeExit)
                        return;
        
                    if (!event && window.event) 
                              event = window.event;
                              
                       event.returnValue = "' . $this->hotaru->lang['submit_accidental_click'] . '";
                }
                
                </script>
            ';
        }
    }
    
    
    /**
     * Add "Submit" to the navigation bar
     */
    public function navigation()
    {
        // return false if not logged in or submission disabled
        if (!$this->hotaru->currentUser->loggedIn) { return false; }
        //if (!$this->hotaru->post->useSubmission) { return false; }
        
        // highlight "Submit" as active tab
        if ($this->hotaru->pageType == 'submit') { $status = "id='navigation_active'"; } else { $status = ""; }
        
        // display the link in the navigation bar
        echo "<li><a  " . $status . " href='" . $this->hotaru->url(array('page'=>'submit')) . "'>" . $this->hotaru->lang['submit_submit_a_story'] . "</a></li>\n";
    }
    
    
    /**
     * Determine which template to show and do preparation of variables, etc.
     */
    public function theme_index_main()
    {
        switch ($this->hotaru->pageName)
        {
            // Submit Step 1
            case 'submit1':
                $this->hotaru->displayTemplate('submit_step1');
                return true;
                break;
                
            // Submit Step 2
            case 'submit2':
            
                // settings
                $this->hotaru->vars['submit_use_content'] = $this->hotaru->vars['submit_settings']['content'];
                $this->hotaru->vars['submit_content_length'] = $this->hotaru->vars['submit_settings']['content_length'];
                $allowable_tags = $this->hotaru->vars['submit_settings']['allowable_tags'];
                $this->hotaru->vars['submit_allowable_tags'] = htmlentities($allowable_tags);
                
                // submitted data
                $this->hotaru->vars['submit_use_link'] = $this->hotaru->vars['submitted_data']['submit_use_link'];
                $this->hotaru->vars['submit_orig_url'] = urldecode($this->hotaru->vars['submitted_data']['submit_orig_url']);
                $this->hotaru->vars['submit_title'] = sanitize($this->hotaru->vars['submitted_data']['submit_title'], 1);
                $this->hotaru->vars['submit_content'] = sanitize($this->hotaru->vars['submitted_data']['submit_content'], 1);
                $this->hotaru->vars['submit_post_id'] = $this->hotaru->vars['submitted_data']['submit_id'];
    
                $this->hotaru->displayTemplate('submit_step2');
                return true;
                break;
                
            // Submit Step 3
            case 'submit3':
                $this->hotaru->displayTemplate('submit_step3');
                return true;
                break;
        }
    }


}
?>