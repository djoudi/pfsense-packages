<?php
/* $Id: load_balancer_pool.php,v 1.5.2.6 2007/03/02 23:48:32 smos Exp $ */
/*
	haproxy_global.php
	part of pfSense (http://www.pfsense.com/)
	Copyright (C) 2009 Scott Ullrich <sullrich@pfsense.com>
	Copyright (C) 2008 Remco Hoef <remcoverhoef@pfsense.com>
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

require("globals.inc");
require("guiconfig.inc");
require_once("haproxy.inc");

$d_haproxyconfdirty_path = $g['varrun_path'] . "/haproxy.conf.dirty";

if (!is_array($config['installedpackages']['haproxy'])) 
	$config['installedpackages']['haproxy'] = array();


if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;
	
	if ($_POST['apply']) {
		$retval = 0;
		config_lock();
		$retval = haproxy_configure();
		config_unlock();
		$savemsg = get_std_save_message($retval);
		unlink_if_exists($d_haproxyconfdirty_path);
	} else {
		if ($_POST['enable']) {
			$reqdfields = explode(" ", "maxconn");
			$reqdfieldsn = explode(",", "Maximum connections");		
		}

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

		if ($_POST['maxconn'] && (!is_numeric($_POST['maxconn']))) 
			$input_errors[] = "The maximum number of connections should be numeric.";

		if($_POST['synchost1'] && !is_ipaddr($_POST['synchost1']))
			$input_errors[] = "Synchost1 needs to be an IPAddress.";
		if($_POST['synchost2'] && !is_ipaddr($_POST['synchost2']))
			$input_errors[] = "Synchost2 needs to be an IPAddress.";
		if($_POST['synchost3'] && !is_ipaddr($_POST['synchost3']))
			$input_errors[] = "Synchost3 needs to be an IPAddress.";

		if (!$input_errors) {
			$config['installedpackages']['haproxy']['enable'] = $_POST['enable'] ? true : false;
			$config['installedpackages']['haproxy']['maxconn'] = $_POST['maxconn'] ? $_POST['maxconn'] : false;
			$config['installedpackages']['haproxy']['enablesync'] = $_POST['enablesync'] ? true : false;
			$config['installedpackages']['haproxy']['synchost1'] = $_POST['synchost1'] ? $_POST['synchost1'] : false;
			$config['installedpackages']['haproxy']['synchost2'] = $_POST['synchost2'] ? $_POST['synchost2'] : false;
			$config['installedpackages']['haproxy']['synchost2'] = $_POST['synchost3'] ? $_POST['synchost3'] : false;
			$config['installedpackages']['haproxy']['syncpassword'] = $_POST['syncpassword'] ? $_POST['syncpassword'] : false;
			touch($d_haproxyconfdirty_path);
			write_config();
		}
	}
	
}

$pconfig['enable'] = isset($config['installedpackages']['haproxy']['enable']);
$pconfig['maxconn'] = $config['installedpackages']['haproxy']['maxconn'];
$pconfig['enablesync'] = isset($config['installedpackages']['haproxy']['enablesync']);
$pconfig['syncpassword'] = $config['installedpackages']['haproxy']['syncpassword'];
$pconfig['synchost1'] = $config['installedpackages']['haproxy']['synchost1'];
$pconfig['synchost2'] = $config['installedpackages']['haproxy']['synchost2'];
$pconfig['synchost3'] = $config['installedpackages']['haproxy']['synchost3'];

$pfSversion = str_replace("\n", "", file_get_contents("/etc/version"));
if(strstr($pfSversion, "1.2"))
	$one_two = true;

$pgtitle = "Services: HAProxy: Settings";
include("head.inc");

?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<script language="JavaScript">
<!--
function enable_change(enable_change) {
	var endis;
	endis = !(document.iform.enable.checked || enable_change);
	document.iform.maxconn.disabled = endis;
}
//-->
</script>
<?php if($one_two): ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php endif; ?>
<form action="haproxy_global.php" method="post" name="iform">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_haproxyconfdirty_path)): ?><p>
<?php print_info_box_np("The load balancer configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabnavtbl">
	<?php
	/* active tabs */
	$tab_array = array();
	$tab_array[] = array("Settings", true, "haproxy_global.php");
	$tab_array[] = array("Frontends/Backends", false, "haproxy_backends.php");	
	$tab_array[] = array("Servers", false, "haproxy_servers.php");	
	display_top_tabs($tab_array);
	?>
	</td></tr>
	<tr>
	<td>
	<div id="mainarea">
		<table class="tabcont" width="100%" border="0" cellpadding="6" cellspacing="0">
			<tr>
				<td colspan="2" valign="top" class="listtopic">General settings</td>
			</tr>
			<tr>
				<td width="22%" valign="top" class="vncell">&nbsp;</td>
				<td width="78%" class="vtable">
				<input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)">
				<strong>Enable HAProxy</strong></td>
			</tr>
			<tr>
				<td valign="top" class="vncell">
					Maximum connections
				</td>
				<td class="vtable">
					<table><tr><td>
					<table cellpadding="0" cellspacing="0">
						<tr>
							<td>
								<input name="maxconn" type="text" class="formfld" id="maxconn" size="5" <?if ($pconfig['enable']!='yes') echo "enabled=\"false\"";?>  value="<?=htmlspecialchars($pconfig['maxconn']);?>"> per Backend.
							</td>
						</tr>
					</table>
					Sets the maximum per-process number of concurrent connections to X.<br/>
					<strong>NOTE:</strong> setting this value too high will result in haproxy not being able to allocate enough memory.<br/>
				<?php
					$hascpu = trim(`top | grep haproxy | awk '{ print $6 }'`);
					if($hascpu)
						echo "<p>Current memory usage {$hascpu}.</p>";
				?>
					</td><td>
					<table style="border: 1px solid #000;">
						<tr>
							<td><font size=-1>Connections</td>
							<td><font size=-1>Memory usage</td>
						</tr>
						<tr>
							<td colspan="2">
								<hr noshade style="border: 1px solid #000;">
							</td>
						</tr>
						<tr>
							<td align="right"><font size=-1>999</td>
							<td><font size=-1>1888K</td>
						</tr>
						<tr>
							<td align="right"><font size=-1>99999</td>
							<td><font size=-1>8032K</td>
						</tr>
						<tr>
							<td align="right"><font size=-1>999999</td>
							<td><font size=-1>50016K</td>
						</tr>
						<tr>
							<td align="right"><font size=-1>9999999</td>
							<td><font size=-1>467M</td>
						</tr>
					</table>
					</td></tr></table>
				</td>
			</tr>
			<tr>
				<td>
					&nbsp;
				</td>
			</tr>
			<tr>
				<td colspan="2" valign="top" class="listtopic">Configuration synchronization</td>
			</tr>
			<tr>
				<td width="22%" valign="top" class="vncell">&nbsp;</td>
				<td width="78%" class="vtable">
					<input name="enablesync" type="checkbox" value="yes" <?php if ($pconfig['enablesync']) echo "checked"; ?>>
					<strong>Sync HAProxy configuration to backup CARP members via XMLRPC.</strong>
				</td>
			</tr>
			<tr>
				<td width="22%" valign="top" class="vncell">Synchronization password</td>
				<td width="78%" class="vtable">
					<input name="syncpassword" type="password" value="<?=$pconfig['syncpassword'];?>">
					<br/>
					<strong>Enter the password that will be used during configuration synchronization.  This is generally the remote webConfigurator password.</strong>
				</td>
			</tr>
			<tr>
				<td width="22%" valign="top" class="vncell">Sync host #1</td>
				<td width="78%" class="vtable">
					<input name="synchost1" value="<?=$pconfig['synchost1'];?>">
					<br/>
					<strong>Synchronize settings to this hosts IP address.</strong>
				</td>
			</tr>
			<tr>
				<td width="22%" valign="top" class="vncell">Sync host #2</td>
				<td width="78%" class="vtable">
					<input name="synchost2" value="<?=$pconfig['synchost2'];?>">
					<br/>
					<strong>Synchronize settings to this hosts IP address.</strong>
				</td>
			</tr>
			<tr>
				<td width="22%" valign="top" class="vncell">Sync host #3</td>
				<td width="78%" class="vtable">
					<input name="synchost3" value="<?=$pconfig['synchost3'];?>">
					<br/>
					<strong>Synchronize settings to this hosts IP address.</strong>
				</td>
			</tr>
			<tr>
				<td>
					&nbsp;
				</td>
			</tr>
			<tr>
				<td width="22%" valign="top">&nbsp;</td>
					<td width="78%">
						<input name="Submit" type="submit" class="formbtn" value="Save" onClick="enable_change(true)">
					</td>
				</td>
			</tr>
		</table>
	</div>
</table>
</form>
<script language="JavaScript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc"); ?>
</body>
</html>