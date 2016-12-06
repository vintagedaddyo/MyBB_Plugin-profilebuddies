<?php
/*
 * MyBB: Profile Buddies
 *
 * File: profilebuddies.php
 * 
 * Authors: Sebastian Wunderlich & Vintagedaddyo
 *
 * MyBB Version: 1.8
 *
 * Plugin Version: 1.4.2
 * 
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook('member_profile_end','profilebuddies');
$plugins->add_hook('usercp_do_editlists_end','profilebuddies_message');

function profilebuddies_info()
{
    global $lang;

    $lang->load("profilebuddies");
    
    $lang->profilebuddies_Desc = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float:right;">' .
        '<input type="hidden" name="cmd" value="_s-xclick">' . 
        '<input type="hidden" name="hosted_button_id" value="AZE6ZNZPBPVUL">' .
        '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">' .
        '<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">' .
        '</form>' . $lang->profilebuddies_Desc;

    return Array(
        'name' => $lang->profilebuddies_Name,
        'description' => $lang->profilebuddies_Desc,
        'website' => $lang->profilebuddies_Web,
        'author' => $lang->profilebuddies_Auth,
        'authorsite' => $lang->profilebuddies_AuthSite,
        'version' => $lang->profilebuddies_Ver,
        'codename' => $lang->profilebuddies_CodeName,
        'compatibility' => $lang->profilebuddies_Compat
    );
}

function profilebuddies_activate()
{
	global $db;
	$info=profilebuddies_info();
	$setting_group_array=array
	(
		'name'=>$info['codename'],
		'title'=>$info['name'],
		'description'=>'Here you can edit '.$info['name'].' settings.',
		'disporder'=>1,
		'isdefault'=>0
	);
	$db->insert_query('settinggroups',$setting_group_array);
	$group=$db->insert_id();
	$settings=array
	(
		'profilebuddies_limit'=>array
		(
			'Limit Buddies',
			'Limits the number of buddies that will be displayed in profile.',
			'text',
			10
		),
		'profilebuddies_limit_overwrite'=>array
		(
			'Show All Buddies?',
			'Do you want to give your members the possibility to view all buddies without limitation?',
			'yesno',
			0
		),
		'profilebuddies_order_by'=>array
		(
			'Sort Field',
			'Select the field that you want buddies to be sorted.',
			'select
username=Username
regdate=Registration date
lastvisit=Last visit
postnum=Post count
RAND()=Random',
			'username'
		),
		'profilebuddies_order_dir'=>array
		(
			'Sort Order',
			'Select the order that you want buddies to be sorted.',
			'select
ASC=Ascending
DESC=Descending',
			'ASC'
		),
		'profilebuddies_quicklinks'=>array
		(
			'Show Quick Links?',
			'Do you want to display quick links for adding or removing a buddy?',
			'yesno',
			0
		),
		'profilebuddies_email'=>array
		(
			'Email Notification',
			'If you active this setting your members will get an email notification when someone adds them to buddy list.',
			'onoff',
			0
		),
		'profilebuddies_pm'=>array
		(
			'Private Message Notification',
			'If you active this setting your members will get a private message notification when someone adds them to buddy list.',
			'onoff',
			0
		)
	);
	$i=1;
	foreach($settings as $name=>$sinfo){
		$insert_array=array
		(
			'name'=>$name,
			'title'=>$db->escape_string($sinfo[0]),
			'description'=>$db->escape_string($sinfo[1]),
			'optionscode'=>$db->escape_string($sinfo[2]),
			'value'=>$db->escape_string($sinfo[3]),
			'gid'=>$group,
			'disporder'=>$i,
			'isdefault'=>0
		);
		$db->insert_query('settings',$insert_array);
		$i++;
	}
	rebuild_settings();
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('member_profile','#<br />{\$buddylist}#i','',0);
	find_replace_templatesets('member_profile','#{\$buddylist}#i','',0);
	find_replace_templatesets('member_profile','#{\$modoptions}#i','{$buddylist}<br />{$modoptions}');
}

function profilebuddies_deactivate()
{
	global $db;
	$info=profilebuddies_info();
	$result=$db->simple_select('settinggroups','gid','name="'.$info['codename'].'"',array('limit'=>1));
	$group=$db->fetch_array($result);
	if(!empty($group['gid']))
	{
		$db->delete_query('settinggroups','gid="'.$group['gid'].'"');
		$db->delete_query('settings','gid="'.$group['gid'].'"');
		rebuild_settings();
	}
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('member_profile','#<br />{\$buddylist}#i','',0);
	find_replace_templatesets('member_profile','#{\$buddylist}#i','',0);
}

function profilebuddies_lang()
{
	global $lang;
	$lang->load('profilebuddies',false,true);
	$l['profilebuddies_title']='{1}\'s buddies';
	$l['profilebuddies_title_all']='Show all {1} buddies';
	$l['profilebuddies_no_buddies']='{1} doesn\'t have add any buddy.';
	$l['profilebuddies_add_buddy']='Add {1} to your buddy list!';
	$l['profilebuddies_remove_buddy']='Remove {1} from your buddy list!';
	$l['profilebuddies_email_subject']='Someone added you to buddy list at {1}';
	$l['profilebuddies_email_message']='{1},

{2} from {3} has added you to buddy list. To view {2}\'s profile, you can follow this link:

{4}/{5}

Thank you,
{3} Staff
{4}';
	$l['profilebuddies_pm_subject']='I added you to my buddy list';
	$l['profilebuddies_pm_message']='Hi {1},

I added you to my buddy list. Click [url={2}]here[/url] to view my profile.

Best regards,
{3}';
	foreach($l as $key=>$val)
	{
		if(!$lang->$key)
		{
			$lang->$key=$val;
		}
	}
}

function profilebuddies()
{
	global $mybb,$lang,$db,$theme,$memprofile,$buddylist;
	profilebuddies_lang();
	if(!empty($memprofile['buddylist']))
	{
		$options=array
		(
			'order_by'=>$mybb->settings['profilebuddies_order_by'],
			'order_dir'=>$mybb->settings['profilebuddies_order_dir']
		);
		$buddies_full=explode(',',$memprofile['buddylist']);
		$buddies_count=count($buddies_full);
		if($mybb->settings['profilebuddies_limit_overwrite']==0)
		{
			$options['limit']=$mybb->settings['profilebuddies_limit'];
		}
		elseif(intval($mybb->input['buddies'])==1)
		{
			$options['limit']=$buddies_count;
		}
		else
		{
			$options['limit']=$mybb->settings['profilebuddies_limit'];
			if($buddies_count>$mybb->settings['profilebuddies_limit'])
			{
				$showall=$lang->sprintf($lang->profilebuddies_title_all,$buddies_count);
			}
		}
		$query=$db->simple_select('users','uid,username,avatar,avatardimensions,usergroup,displaygroup','uid IN('.$memprofile['buddylist'].')',$options);
		list($max_width,$max_height)=explode('x',my_strtolower($mybb->settings['postmaxavatarsize']));
		require_once MYBB_ROOT.'inc/functions_image.php';
		while($buddy=$db->fetch_array($query))
		{
			$avatar_default=$theme['imgdir'].'/default_avatar.png';
			if(!($buddy['avatar']))
			{
				$buddy['avatar']=$theme['imgdir'].'/default_avatar.png';
				$buddy['avatardimensions']='44|44';
				$avatar_default='style="border:1px solid #cccccc;" ';
			}
			$buddy['avatar']=htmlspecialchars_uni($buddy['avatar']);
			$avatar_dimensions=explode('|',$buddy['avatardimensions']);
			if($avatar_dimensions[0]&&$avatar_dimensions[1])
			{
				if($avatar_dimensions[0]>$max_width||$avatar_dimensions[1]>$max_height)
				{
					$scaled_dimensions=scale_image($avatar_dimensions[0],$avatar_dimensions[1],$max_width,$max_height);
					$avatar_width_height='width="'.$scaled_dimensions['width'].'" height="'.$scaled_dimensions['height'].'"';
				}
				else
				{
					$avatar_width_height='width="'.$avatar_dimensions[0].'" height="'.$avatar_dimensions[1].'"';
				}
				$buddy_avatar='<a href="'.get_profile_link($buddy['uid']).'"><img src="'.$buddy['avatar'].'" alt="'.htmlspecialchars_uni($buddy['username']).'" '.$avatar_width_height.' '.$avatar_default.'/></a>';
			}
			$buddy_name=format_name($buddy['username'],$buddy['usergroup'],$buddy['displaygroup']);
			$buddy_link='<span>'.build_profile_link($buddy_name,$buddy['uid']).'</span>';
			$buddies.='<li style="display:inline-block;padding:10px;text-align:center;">'.$buddy_avatar.'<br />'.$buddy_link.'</li>';
		}
		$buddies='<ul style="margin:0;padding:0;">'.$buddies.'</ul>';
	}
	else
	{
		$buddies=$lang->sprintf($lang->profilebuddies_no_buddies,htmlspecialchars_uni($memprofile['username']));
	}
	if($mybb->user['uid']!=0&&$mybb->settings['profilebuddies_quicklinks']==1&&$mybb->user['uid']!=$memprofile['uid'])
	{
		$user_buddys=explode(',',$mybb->user['buddylist']);
		if(in_array($memprofile['uid'],$user_buddys))
		{
			$addremove='<tr><td class="trow2"><a href="'.$mybb->settings['bburl'].'/usercp.php?action=do_editlists&amp;delete='.$memprofile['uid'].'&amp;my_post_key='.$mybb->post_code.'">'.$lang->sprintf($lang->profilebuddies_remove_buddy,htmlspecialchars_uni($memprofile['username'])).'</a></td></tr>';
		}
		else
		{
			$addremove='<tr><td class="trow2"><a href="'.$mybb->settings['bburl'].'/usercp.php?action=do_editlists&amp;add_username='.htmlspecialchars_uni($memprofile['username']).'&amp;my_post_key='.$mybb->post_code.'">'.$lang->sprintf($lang->profilebuddies_add_buddy,htmlspecialchars_uni($memprofile['username'])).'</a></td></tr>';
		}
	}
	$title='<strong>'.$lang->sprintf($lang->profilebuddies_title,htmlspecialchars_uni($memprofile['username'])).'</strong>';
	if($showall)
	{
		$link=get_profile_link($memprofile['uid']);
		if(my_strpos($link,'?'))
		{
			$link=$link.'&buddies=1';
		}
		else
		{
			$link=$link.'?buddies=1';
		}
		$title='<div class="float_right"><a href="'.$link.'">'.$showall.'</a></div><div>'.$title.'</div>';
	}
	$buddylist='<table border="0" cellspacing="'.$theme['borderwidth'].'" cellpadding="'.$theme['tablespace'].'" class="tborder"><tbody><tr><td class="thead">'.$title.'</td></tr><tr><td class="trow1 smalltext">'.$buddies.'</td></tr>'.$addremove.'</tbody></table>';
}

function profilebuddies_message()
{
	global $mybb,$lang,$message,$error_message,$db,$users;
	profilebuddies_lang();
	if($mybb->settings['profilebuddies_email']==1||$mybb->settings['profilebuddies_pm']==1)
	{
		if($message==$lang->users_added_to_buddy_list&&empty($error_message))
		{
			require_once MYBB_ROOT.'inc/datahandlers/pm.php';
			foreach($users as $user)
			{
				$query=$db->simple_select('users','uid,email','username="'.$user.'"');
				$result=$db->fetch_array($query);
				if($mybb->settings['profilebuddies_email']==1)
				{
					$subject=$lang->sprintf($lang->profilebuddies_email_subject,$mybb->settings['bbname']);
					$body=$lang->sprintf($lang->profilebuddies_email_message,$user,$mybb->user['username'],$mybb->settings['bbname'],$mybb->settings['bburl'],get_profile_link($mybb->user['uid']));
					my_mail($result['email'],$subject,$body);
				}
				if($mybb->settings['profilebuddies_pm']==1)
				{
					$subject=$lang->sprintf($lang->profilebuddies_pm_subject);
					$body=$lang->sprintf($lang->profilebuddies_pm_message,$user,$mybb->settings['bburl'].'/'.htmlspecialchars_decode(get_profile_link($mybb->user['uid'])),$mybb->user['username']);
					$pmhandler=new PMDataHandler();
					$pm=array(
						'subject'=>$subject,
						'message'=>$body,
						'toid'=>array($result['uid']),
						'options'=>array
						(
							'savecopy'=>0
						),
						'fromid'=>$mybb->user['uid']
					);
					$pmhandler->admin_override=true;
					$pmhandler->set_data($pm);
					$pmhandler->validate_pm();
					$pmhandler->insert_pm();
				}
			}
		}
	}
}

?>