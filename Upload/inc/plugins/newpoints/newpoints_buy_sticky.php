<?php

/***************************************************************************
 *
 *	Newpoints Buy Sticky plugin (/inc/plugins/newpoints/newpoints_buy_sticky.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2016 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Allows users to buy sticky statuses for their own threads.
 *
 ***************************************************************************

****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Disallow direct access to this file for security reasons
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// Plugin API
function newpoints_buy_sticky_info()
{
	global $lang, $ougc_newpoints_buy_sticky;
	$ougc_newpoints_buy_sticky->load_language();

	return array(
		'name'			=> 'Newpoints Buy Sticky',
		'description'	=> $lang->setting_buy_sticky_desc,
		'website'		=> 'http://omarg.me',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '1.0 BETA',
		'versioncode'	=> 1000,
		'compatibility'	=> '2*'
	);
}

// _activate() routine
function newpoints_buy_sticky_activate()
{
	global $db, $lang, $cache, $ougc_newpoints_buy_sticky;
	$ougc_newpoints_buy_sticky->load_language();

	/*

	title:
		newpoints_buy_sticky_option

	contents:
<td width="{$width}%">
	<form action="{$mybb->settings['bburl']}/newpoints.php" method="post">
		<input type="hidden" name="action" value="do_shop">
		<input type="hidden" name="shop_action" value="buy_sticky">
		<input type="hidden" name="iid" value="{$item['iid']}">
		<input type="hidden" name="postcode" value="{$mybb->post_code}">
		<input type="submit" name="submit" value="{$lang->newpoints_buy_sticky_redeem}">
	</form>
</td>


*/

	// Add the button variable
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('newpoints_shop_myitems_item', '#'.preg_quote('{$send}').'#', '<!--NEWPOINTS_BUY_STICKY[{$item[\'iid\']}]-->{$send}');

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = newpoints_buy_sticky_info();

	if(!isset($plugins['newpoints_buy_sticky']))
	{
		$plugins['newpoints_buy_sticky'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/
	if($plugins['newpoints_buy_sticky'] <= 1000)
	{
	}
	/*~*~* RUN UPDATES END *~*~*/

	$plugins['newpoints_buy_sticky'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// _deactivate
function newpoints_buy_sticky_deactivate()
{
	// Remove the button variable
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('newpoints_shop_myitems_item', '#'.preg_quote('<!--NEWPOINTS_BUY_STICKY[{$item[\'iid\']}]-->').'#', '',0);
}

// _install
function newpoints_buy_sticky_install()
{
	global $db;

	$db->field_exists('buy_sticky', 'newpoints_shop_items') or $db->add_column('newpoints_shop_items', 'buy_sticky', "bool NOT NULL DEFAULT 0");
	$db->field_exists('buy_sticky_time', 'newpoints_shop_items') or $db->add_column('newpoints_shop_items', 'buy_sticky_time', "int(10) NOT NULL DEFAULT 0");
}

// _uninstall
function newpoints_buy_sticky_uninstall()
{
	global $db, $cache;

	// Remove the plugin columns, if any...
	!$db->field_exists('buy_sticky', 'newpoints_shop_items') or $db->drop_column('newpoints_shop_items', 'buy_sticky');
	!$db->field_exists('buy_sticky_time', 'newpoints_shop_items') or $db->drop_column('newpoints_shop_items', 'buy_sticky_time');

	// Clean any logs from this plugin.
	newpoints_remove_log(array('buy_sticky'));

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['newpoints_buy_sticky']))
	{
		unset($plugins['newpoints_buy_sticky']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$cache->delete('ougc_plugins');
	}
}

// _is_insalled
function newpoints_buy_sticky_is_installed()
{
	global $db;

	return $db->field_exists('buy_sticky', 'newpoints_shop_items');
}

class OUGC_Newpoints_Buy_Sticky
{
	function __construct()
	{
		global $plugins;

		// Tell MyBB when to run the hook
		if(defined('IN_ADMINCP'))
		{
			$plugins->add_hook('newpoints_shop_row', array($this, 'hook_newpoints_shop_row'));
			$plugins->add_hook('newpoints_shop_commit', array($this, 'hook_newpoints_shop_commit'));
		}
		else
		{
			$plugins->add_hook('newpoints_shop_end', array($this, 'hook_newpoints_shop_end'));
			$plugins->add_hook('newpoints_do_shop_start', array($this, 'hook_newpoints_do_shop_start'));

			if(THIS_SCRIPT == '.php')
			{
				global $templatelist;

				if(isset($templatelist))
				{
					$templatelist .= ',';
				}
				else
				{
					$templatelist = '';
				}
				$templatelist .= '';
			}
		}
	}

	function load_language()
	{
		global $lang;

		isset($lang->newpoints_buy_sticky) or newpoints_lang_load('newpoints_buy_sticky');
	}

	function hook_newpoints_shop_row(&$args)
	{
		global $lang;
		$this->load_language();

		$args[0]->output_row($lang->newpoints_buy_sticky, $lang->newpoints_buy_sticky_desc, $args[1]->generate_yes_no_radio('buy_sticky', (int)$args[2]['buy_sticky']), 'buy_sticky');
		$args[0]->output_row($lang->newpoints_buy_sticky_time, $lang->newpoints_buy_sticky_time_desc, $args[1]->generate_text_box('buy_sticky_time', (int)$args[2]['buy_sticky_time'], array('id' => 'buy_sticky_time')), 'buy_sticky_time');
	}

	function hook_newpoints_shop_commit(&$args)
	{
		global $mybb;

		$args['buy_sticky'] = $mybb->get_input('buy_sticky', 1);
		$args['buy_sticky_time'] = $mybb->get_input('buy_sticky_time', 1);
	}

	function hook_newpoints_shop_end()
	{
		global $mybb, $plugins;

		if($mybb->get_input('shop_action') == 'myitems')
		{
			$plugins->add_hook('pre_output_page', array($this, 'hook_pre_output_page'));
		}
	}

	function hook_pre_output_page(&$page)
	{
		global $mybb;

		preg_match_all('#\<\!--NEWPOINTS_BUY_STICKY\[([0-9]+)\]--\>#i', $page, $matches);

		$matches = array_unique(array_map('intval', $matches[1]));

		if(!$matches)
		{
			return;
		}

		global $db, $lang, $templates;
		$this->load_language();

		// this is probably unnecessary
		$per_page = 10;
		$start = 0;
		if($mybb->get_input('page', 1) > 1)
		{
			$start = ($mybb->get_input('page', 1)*$per_page)-$per_page;
		}

		$replacements = array();
		$query = $db->simple_select('newpoints_shop_items', '*', "visible=1 AND iid IN (".implode(',', $matches).")", array('limit' => $per_page, 'limit_start' => $start));
		while($item = $db->fetch_array($query))
		{
			$width = '100';
			if(($mybb->settings['newpoints_shop_sendable'] && $item['sellable']) || ($mybb->settings['newpoints_shop_sellable'] && $item['sendable']))
			{
				$width = '50';
			}

			$item['iid'] = (int)$item['iid'];

			$replacements = array("<!--NEWPOINTS_BUY_STICKY[{$item['iid']}]-->" => eval($templates->render('newpoints_buy_sticky_option')));
		}

		if($replacements)
		{
			$page = str_replace(array_keys($replacements), array_values($replacements), $page);
		}
	}

	function hook_newpoints_do_shop_start()
	{
		global $mybb, $db, $lang, $cache, $theme, $header, $templates, $plugins, $headerinclude, $footer, $options, $inline_errors;

		if($mybb->get_input('shop_action') == 'buy_sticky')
		{
			$do = false;
		}
		elseif($mybb->get_input('shop_action') == 'do_buy_sticky')
		{
			$do = true;
		}
		else
		{
			return false;
		}

		if($do)
		{
			$plugins->run_hooks('newpoints_shop_do_buy_sticky_start');
		}
		else
		{
			$plugins->run_hooks('newpoints_shop_buy_sticky_start');
		}

		if(!($item = newpoints_shop_get_item($mybb->get_input('iid', 1))))
		{
			error($lang->newpoints_shop_invalid_item);
		}

		if(!($cat = newpoints_shop_get_category($item['cid'])))
		{
			error($lang->newpoints_shop_invalid_cat);
		}

		if(!newpoints_shop_check_permissions($cat['usergroups']))
		{
			error_no_permission();
		}

		if(!$item['visible'] || !$cat['visible'])
		{
			error_no_permission();
		}

		if(!$item['buy_sticky'] || $item['buy_sticky_time'] < 1)
		{
			error_no_permission();
		}

		$myitems = @unserialize($mybb->user['newpoints_items']);
		if(!$myitems)
		{
			error($lang->newpoints_shop_inventory_empty);
		}

		$key = array_search($item['iid'], $myitems);
		if($key === false)
		{
			error($lang->newpoints_shop_selected_item_not_owned);
		}

		$this->load_language();

		if($do)
		{
			// ~~~ @ https://github.com/PaulBender/Move-Posts/blob/master/inc/plugins/moveposts.php#L217 //
			if($db->table_exists('google_seo'))
			{
				$regexp = "{$mybb->settings['bburl']}/{$mybb->settings['google_seo_url_threads']}";
				if($regexp)
				{
				$regexp = preg_quote($regexp, '#');
				$regexp = str_replace('\\{\\$url\\}', '([^./]+)', $regexp);
				$regexp = str_replace('\\{url\\}', '([^./]+)', $regexp);
				$regexp = "#^{$regexp}$#u";
				}

				$url = $mybb->get_input('threadurl');

				$url = preg_replace('/^([^#?]*)[#?].*$/u', '\\1', $url);

				$url = preg_replace($regexp, '\\1', $url);

				$url = urldecode($url);

				$query = $db->simple_select('google_seo', 'id', "idtype='4' AND url='{$db->escape_string($url)}'");
				$redeemtid = $db->fetch_field($query, 'id');
			}
			$realurl = explode('#', $mybb->get_input('threadurl'));
			$mybb->input['threadurl'] = $realurl[0];

			if(substr($mybb->get_input('threadurl'), -4) == 'html')
			{
				preg_match('#thread-([0-9]+)?#i', $mybb->get_input('threadurl'), $threadmatch);
				preg_match('#post-([0-9]+)?#i', $mybb->get_input('threadurl'), $postmatch);

				if($threadmatch[1])
				{
					$parameters['tid'] = $threadmatch[1];
				}

				if($postmatch[1])
				{
					$parameters['pid'] = $postmatch[1];
				}
			}
			else
			{
				$splitloc = explode('.php', $mybb->get_input('threadurl'));
				$temp = explode('&', my_substr($splitloc[1], 1));
				if(!empty($temp))
				{
					for($i = 0; $i < count($temp); $i++)
					{
						$temp2 = explode('=', $temp[$i], 2);
						$parameters[$temp2[0]] = $temp2[1];
					}
				}
				else
				{
					$temp2 = explode('=', $splitloc[1], 2);
					$parameters[$temp2[0]] = $temp2[1];
				}
			}

			if($parameters['pid'] && !$parameters['tid'])
			{
				$query = $db->simple_select('posts', '*', "pid='".(int)$parameters['pid']."'");
				$post = $db->fetch_array($query);
				$redeemtid = $post['tid'];
			}
			elseif($parameters['tid'])
			{
				$redeemtid = $parameters['tid'];
			}

			$thread = get_thread($redeemtid);
			// ~~~ //

			if(!$thread['tid'] || !$thread['visible'] || $thread['deletetime'])
			{
				error($lang->newpoints_buy_sticky_redeem_error_invalid);
			}

			if($thread['sticky'])
			{
				error($lang->newpoints_buy_sticky_redeem_error_alreadystickied);
			}

			if($thread['closed'])
			{
				error($lang->newpoints_buy_sticky_redeem_error_closedthread);
			}

			if($thread['uid'] != $mybb->user['uid'])
			{
				error($lang->newpoints_buy_sticky_redeem_error_wronguser);
			}

			// We need more extensive permission checkings here late on..

			require_once MYBB_ROOT.'inc/class_moderation.php';
			$moderation = new Moderation;

			$lang->load('moderation');

			$moderation->stick_threads($thread['tid']);

			log_moderator_action(array('fid' => $thread['fid'], 'tid' => $thread['tid']), $lang->sprintf($lang->mod_process, $lang->stuck));

			newpoints_log('buy_sticky', $mybb->settings['bburl'].'/'.get_thread_link($thread['tid']), $mybb->user['username'], $mybb->user['uid']);

			$rundate = TIME_NOW + ($item['buy_sticky_time']*86400);

			$did = $db->insert_query("delayedmoderation", array(
				'type'			=> $db->escape_string('stick'),
				'delaydateline'	=> (int)$rundate,
				'uid'			=> (int)$mybb->user['uid'],
				'tids'			=> (int)$thread['tid'],
				'fid'			=> (int)$thread['fid'],
				'dateline'		=> TIME_NOW,
				'inputs'		=> $db->escape_string(my_serialize(array(
					'new_forum'			=> (int)$thread['fid'],
					'method'			=> 'move',
					'redirect_expire'	=> ''
				)))
			));

			$plugins->run_hooks('moderation_do_delayedmoderation');

			// remove item from our inventory
			unset($myitems[$key]);
			sort($myitems);
			$db->update_query('users', array('newpoints_items' => serialize($myitems)), "uid='".(int)$mybb->user['uid']."'");

			$plugins->run_hooks('newpoints_shop_do_buy_sticky_end');

			$message = $lang->sprintf($lang->newpoints_buy_sticky_redeem_done, my_date('relative', $rundate, '', 2));

			redirect($mybb->settings['bburl'].'/newpoints.php?action=shop&amp;shop_action=myitems', $message, $lang->newpoints_buy_sticky_redeem_done_title);
		}
		else
		{
			$lang->newpoints_shop_action = $lang->newpoints_buy_sticky_redeem_title;
			$item['name'] = htmlspecialchars_uni($item['name']);

			global $shop_action, $data, $colspan;
			$colspan = 2;
			$shop_action = 'do_buy_sticky';
			$fields = '<input type="hidden" name="iid" value="'.$item['iid'].'">';
			$data = "<td class=\"trow1\" width=\"50%\"><strong>".$lang->newpoints_buy_sticky_redeem_thread.":</strong><br /><small>".$lang->newpoints_buy_sticky_redeem_message."</small></td><td class=\"trow1\" width=\"50%\"><input type=\"text\" class=\"textbox\" name=\"threadurl\" value=\"\"></td>";

			$plugins->run_hooks('newpoints_shop_buy_sticky_end');

			$page = eval($templates->render('newpoints_shop_do_action'));
			output_page($page);
		}
		exit;
	}

	
}

global $ougc_newpoints_buy_sticky;

$ougc_newpoints_buy_sticky = new OUGC_Newpoints_Buy_Sticky();

// control_object by Zinga Burga from MyBBHacks ( mybbhacks.zingaburga.com ), 1.62
if(!function_exists('control_object'))
{
	function control_object(&$obj, $code)
	{
		static $cnt = 0;
		$newname = '_objcont_'.(++$cnt);
		$objserial = serialize($obj);
		$classname = get_class($obj);
		$checkstr = 'O:'.strlen($classname).':"'.$classname.'":';
		$checkstr_len = strlen($checkstr);
		if(substr($objserial, 0, $checkstr_len) == $checkstr)
		{
			$vars = array();
			// grab resources/object etc, stripping scope info from keys
			foreach((array)$obj as $k => $v)
			{
				if($p = strrpos($k, "\0"))
				{
					$k = substr($k, $p+1);
				}
				$vars[$k] = $v;
			}
			if(!empty($vars))
			{
				$code .= '
					function ___setvars(&$a) {
						foreach($a as $k => &$v)
							$this->$k = $v;
					}
				';
			}
			eval('class '.$newname.' extends '.$classname.' {'.$code.'}');
			$obj = unserialize('O:'.strlen($newname).':"'.$newname.'":'.substr($objserial, $checkstr_len));
			if(!empty($vars))
			{
				$obj->___setvars($vars);
			}
		}
		// else not a valid object or PHP serialize has changed
	}
}