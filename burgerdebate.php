<?php
/**
 * Plugin Name: Burger Debate
 */
function debug($message) {
	if(WP_DEBUG===true) {
		error_log($message);
	}
}
function bd_install() {
	global $wpdb;
	$table1_name = $wpdb->prefix."bd_posts";
	$table2_name = $wpdb->prefix."bd_votes";
	$sql1 = "CREATE TABLE IF NOT EXISTS `$table1_name` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `debate_id` int(16) not null,
  PRIMARY KEY (`id`)
)";
	$sql2 = "CREATE TABLE IF NOT EXISTS `$table2_name` (
	cookie_id int(32) not null,
	vote int(11) not null,
	debate_id int(16) not null,
	UNIQUE (`cookie_id`)
)";
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');
	dbDelta($sql1);
	dbDelta($sql2);
	add_option('user1pass','leftleft');
	add_option('user2pass','rightright');
	add_option('user3pass','middleman');
	add_option('bd_cookie_id',1);
}
register_activation_hook(__FILE__,'bd_install');
function bd_admin_init() {
	register_setting('bd-group','user1pass');
	register_setting('bd-group','user2pass');
	register_setting('bd-group','user3pass');
}
add_action('admin_init','bd_admin_init');
function bd_plugin_settings_page() {
	if(!current_user_can('manage_options')) {
		echo('You don\'t have sufficient permissions to access this page.');
	}
?>
	<div class="wrap">
		<h2>Burger Debate</h2>
		<script type="text/javascript">
			var wordbank="burger debate web site seventeen money class awesome numbers extra chat whiteboard journalism online student classic school mission prepare meaning life leader canvas method mentor".split(' ');
			console.log(wordbank);
			console.log(wordbank.length);
			function generatePass(obj) {
				obj.value=wordbank[Math.floor(Math.random()*wordbank.length)]+wordbank[Math.floor(Math.random()*wordbank.length)]+Math.floor(Math.random()*1000);
			}
		</script>
		<form method="post" action="options.php">
			<?php @settings_fields('bd-group') ?>
			<?php @do_settings_fields('bd-group') ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="user1pass">User 1 Password</label></th>
					<td><input type="text" name="user1pass" id="user1pass" value="<?php echo get_option('user1pass'); ?>" /><button onclick="generatePass(user1pass)">Generate</button></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="user2pass">User 2 Password</label></th>
					<td><input type="text" name="user2pass" id="user2pass" value="<?php echo get_option('user2pass'); ?>" /><button onclick="generatePass(user2pass)">Generate</button></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="user3pass">Moderator Password</label></th>
					<td><input type="text" name="user3pass" id="user3pass" value="<?php echo get_option('user3pass'); ?>" /><button onclick="generatePass(user3pass)">Generate</button></td>
				</tr>
			</table>
			<?php @submit_button(); ?>
		</form>
	</div>
<?php
}
function bd_add_menu() {
	$whatever = add_menu_page('Burger Debate', 'Burger Debate', 'manage_options', 'bd-admin', 'bd_plugin_settings_page');
}
add_action('admin_menu','bd_add_menu');
function bd_plugin_settings_link($links) {
	array_unshift($links, '<a href="admin.php?page=bd-admin">Settings</a>');
	return $links;
}
add_filter("plugin_action_links_".plugin_basename(__FILE__), 'bd_plugin_settings_link');
function bd_shortcode_handler($atts) {
	$ajaxurl = addslashes(admin_url('admin-ajax.php'));
	return <<<HTML
		<style type="text/css">
			.bdpost {border-radius: 10px; border: 1px solid #999; padding: 5px; width: 70%; position: relative}
			.bdpost p {display: inline}
			.bdbutton {
   border: 1px solid #000000;
   background: #2f447d;
   background: -webkit-gradient(linear, left top, left bottom, from(#495d9b), to(#2f447d));
   background: -webkit-linear-gradient(top, #495d9b, #2f447d);
   background: -moz-linear-gradient(top, #495d9b, #2f447d);
   background: -ms-linear-gradient(top, #495d9b, #2f447d);
   background: -o-linear-gradient(top, #495d9b, #2f447d);
   padding: 10px 5px;
   -webkit-border-radius: 2px;
   -moz-border-radius: 2px;
   border-radius: 2px;
   -webkit-box-shadow: rgba(0,0,0,.4) 0 2px 0;
   -moz-box-shadow: rgba(0,0,0,.4) 0 2px 0;
   box-shadow: rgba(0,0,0,.4) 0 2px 0;
   text-shadow: rgba(0,0,0,.4) 0 2px 0;
   color: white;
   font-size: 16px;
   font-family: Georgia, Serif;
   text-decoration: none;
   vertical-align: middle;
   margin: 2px
   }
.bdbutton:hover {
   border-top-color: #2b2e65;
   background: #2b2e65;
   color: #ffffff;
   -webkit-box-shadow: rgba(0,0,0,.4) 0 1px 0;
   -moz-box-shadow: rgba(0,0,0,.4) 0 1px 0;
   box-shadow: rgba(0,0,0,.4) 0 1px 0;
   }
.bdbutton:active {
   border-top-color: #2b2f65;
   background: #2b2f65;
   }
			.user1post {background: radial-gradient(#f00, #b00); background: -webkit-radial-gradient(#f00, #b00); margin-right: 15%}
			.user2post {background: radial-gradient(#00f, #00b); background: -webkit-radial-gradient(#00f, #00b); margin-left: 15%}
			.user3post {background: radial-gradient(#0f0, #0b0); background: -webkit-radial-gradient(#0f0, #0b0); margin-right: 7%; margin-left: 7%}
			.modbox {display: none; height: 0px}
			.bd_mod_loggedin div .modbox {display: block; height: auto; font-size: 8pt; color: #0a0}
		</style>
		<script type="text/javascript">
			var bdplugindebugdata = {};
			var ajaxurl="$ajaxurl";
			function loadBDPosts() {
				jQuery.getJSON(
					ajaxurl,
					{
						action: 'loadBDPosts',
					},
					function(d) {
						var doMessage = true;
						console.log(d);
						for(var p in d) {
							doMessage=false;
							var post = document.createElement('div');
							var txt = document.createElement('p');
							txt.className='bdposttxt';
							var txtbox = document.createElement('textarea');
							txtbox.className='bdtxtbox';
							txtbox.style.display='none';
							var savebtn=document.createElement('button');
							savebtn.className='bdbutton bdsavebtn';
							savebtn.style.display='none';
							post.className='user'+d[p].user_id+'post bdpost';
							txt.innerHTML=d[p].text;
							post.appendChild(txt);
							post.appendChild(txtbox);
							post.appendChild(savebtn);
							var modbox = document.createElement('div');
							modbox.className='modbox';
							var editbtn = document.createElement('a');
							editbtn.innerHTML='Edit';
							savebtn.bdPostId=d[p].id;
							savebtn.bdPostDiv=post;
							console.log(editbtn.bdPostId);
							editbtn.onclick=function() {
								this.parentNode.style.display='none';
								this.parentNode.parentNode.getElementsByClassName('bdtxtbox')[0].value=this.parentNode.parentNode.getElementsByClassName('bdposttxt')[0].innerHTML;
								this.parentNode.parentNode.getElementsByClassName('bdtxtbox')[0].style.display='inline';
								this.parentNode.parentNode.getElementsByClassName('bdposttxt')[0].style.display='none';
								this.parentNode.parentNode.getElementsByClassName('bdsavebtn')[0].style.display='inline';
								this.parentNode.parentNode.getElementsByClassName('bdsavebtn')[0].onclick=function() {
									console.log(this.bdPostId);
									jQuery.ajax({
										url: ajaxurl,
										data: {action: 'ModEditBDPost', bdpostid: this.bdPostId, key: mod_password, text: this.bdPostDiv.getElementsByClassName('bdtxtbox')[0].value},
										type: "POST",
										success: function(d) {
											if(d=="success") {
												reloadBDPosts();
											}
											else {
												alert(d);
											}
										}
									});
								}
							};
							modbox.appendChild(editbtn);
							post.appendChild(modbox);
							document.getElementById('bd-post-area').appendChild(post);
						}
						if(doMessage) {
							document.getElementById('bd-post-area').innerHTML="No posts yet!";
						}
						document.getElementById('loading').style.display='none';
						document.getElementById('bd-post-area').style.display='inline';
					}
				);
			}
			loadBDPosts();
			function reloadBDPosts() {
				document.getElementById('post').value="";
				document.getElementById('bd-form-area').style.display='none';
				document.getElementById('bdformexpand').style.display='inline';
				var bdpa = document.getElementById('bd-post-area');
				bdpa.style.display='none';
				document.getElementById('loading').style.display='block';
				while(bdpa.firstChild) {
					bdpa.removeChild(bdpa.firstChild);
				}
				loadBDPosts();
			}
			function loadBDPoll() {
				jQuery.getJSON(
					ajaxurl,
					{
						action: 'loadBDPoll'
					},
					function(d) {
						console.log(d);
						var cnvs = document.createElement('canvas');
						cnvs.width=100;
						cnvs.height=100;
						var ctx = cnvs.getContext('2d');
						if(!d.counts[1]){d.counts[1]=0;}
						if(!d.counts[2]){d.counts[2]=0;}
						var totalvotes = d.counts[1]+d.counts[2];
						console.log(totalvotes);
						var linepos = d.counts[1]*2*Math.PI/totalvotes;
						console.log(linepos);
						ctx.fillStyle="red";
						ctx.beginPath();
						ctx.moveTo(50,50);
						ctx.arc(50,50,50,0,linepos);
						ctx.lineTo(50,50);
						ctx.fill();
						ctx.fillStyle="blue";
						ctx.beginPath();
						ctx.moveTo(50,50);
						ctx.arc(50,50,50,linepos,Math.PI*2);
						ctx.lineTo(50,50);
						ctx.fill();
						document.getElementById('bd_poll_chart').src=cnvs.toDataURL();
						var bdvf = document.getElementById('bd_vote');
						bdvf.onsubmit=function(e){e.preventDefault();return false;}
						var u1o = document.createElement('input');
						var u2o = document.createElement('input');
						u1o.type="radio";
						u2o.type="radio";
						u1o.name="bdpollvote";
						u2o.name="bdpollvote";
						u1o.id="u1o";
						u2o.id="u2o";
						var u1l = document.createElement('label');
						var u2l = document.createElement('label');
						u1l.appendChild(u1o);
						u2l.appendChild(u2o);
						u1l.innerHTML+="Red";
						u2l.innerHTML+="Blue";
						bdvf.appendChild(u1l);
						bdvf.appendChild(u2l);
						if(d.uservote=="1"){document.getElementById('u1o').checked='checked';}
						else if(d.uservote=="2"){document.getElementById('u2o').checked='checked';}
						var votebutton = document.createElement('button');
						votebutton.innerHTML='Vote';
						votebutton.className='bdbutton';
						votebutton.onclick=function() {
							var uservote = 0;
							if(document.getElementById('u2o').checked==true) {uservote=2;}
							if(document.getElementById('u1o').checked==true) {uservote=1;}
							if(uservote!=0) {
								console.log(uservote);
								jQuery.ajax({
									url: ajaxurl,
									data: {
										action: 'BDPollVote',
										vote: uservote
									},
									type: "POST",
									success: function(d) {
										while(bdvf.firstChild) {bdvf.removeChild(bdvf.firstChild);}
										loadBDPoll();
									}
								});
							}
							else {
								alert("Click the debater you are voting for!");
							}
						};
						bdvf.appendChild(votebutton);
					}
				);
			}
			loadBDPoll();
			function postBDMessage() {
				jQuery.ajax({
					dataType: "text",
					type: "POST",
					url: ajaxurl,
					data: {
						action: 'addBDPost',
						key: document.getElementById('bd_login').value,
						text: document.getElementById('post').value
					},
					success: function(d) {
						if(d=="success") {
							reloadBDPosts();
						}
						else {
							alert(d);
						}
					}
				});
			}
			var mod_password;
			function mod_button(ele) {
				mod_password=prompt("Enter mod password: ");
				if(!mod_password) {
					return;
				}
				jQuery.ajax({
					type: "POST",
					url: ajaxurl,
					data: {
						action: 'BDModLogin',
						key: mod_password
					},
					success: function(d) {
						if(d=="success") {
							ele.style.display="none";
							document.getElementById('bd-post-area').className="bd_mod_loggedin";
							document.getElementById('bd_login').value=mod_password;
							document.getElementById('bd_login').style.display="none";
							document.getElementById('maybetext').style.display="inline";
							document.getElementById('maybetext').innerHTML="Posting as moderator.";
							document.getElementById('bdformexpand').innerHTML='Post';
						}
						else {
							alert("Incorrect password.");
						}
					}
				});
			}
		</script>
		<div id="bd-area">
			<a onclick="mod_button(this)" style="cursor: pointer">Mod Login</a><br />
			<button class="bdbutton" id="bdformexpand" onclick="this.style.display='none';document.getElementById('bd-form-area').style.display='inline';">Post (only for debaters)</button>
			<div id="bd-form-area" style="display: none">
				<input type="password" id="bd_login" placeholder="Password" style="width: 100%" /><br />
				<p id="maybetext" style="display: none"></p>
				<textarea id="post"></textarea>
				<button class="bdbutton" onclick="if(confirm('Are you sure you want to post?')) {postBDMessage();}">Post</button>
				<button class="bdbutton" onclick="document.getElementById('bd-form-area').style.display='none';document.getElementById('bdformexpand').style.display='inline';">Cancel</button>
			</div>
			<br /><br />
			<p id="loading">Loading...</p>
			<div id="bd-post-area" style="display: none"></div>
			<h4>Voting</h4><img id="bd_poll_chart" /><form id="bd_vote"></form>
		</div>
HTML;
}
add_shortcode('bd-content', 'bd_shortcode_handler');
function bd_load_posts() {
	global $wpdb;
	$table1_name=$wpdb->prefix."bd_posts";
	$rows = $wpdb->get_results("SELECT * FROM $table1_name ORDER BY id;");
	echo json_encode($rows);
	die();
}
add_action('wp_ajax_loadBDPosts','bd_load_posts');
add_action('wp_ajax_nopriv_loadBDPosts','bd_load_posts');
function bd_load_poll() {
	global $wpdb;
	if(!isset($_COOKIE['bd_id'])) {
		$cookie_id=get_option('bd_cookie_id');
		update_option('bd_cookie_id',$cookie_id+1);
	}
	else {
		$cookie_id=$_COOKIE['bd_id'];
	}
	setcookie('bd_id',$cookie_id,time()+60*60*24*365);
	$table2_name=$wpdb->prefix."bd_votes";
	$rows = $wpdb->get_results("SELECT * FROM $table2_name");
	$theirvote=0;
	$votecount=array();
	foreach($rows as $v) {
		if($v->cookie_id==$cookie_id) {
			$theirvote=$v->vote;
		}
		if(!isset($votecount[$v->vote])){$votecount[$v->vote]=0;}
		$votecount[$v->vote]++;
	}
	echo json_encode(array('uservote'=>$theirvote,'counts'=>$votecount));
	die();
}
add_action('wp_ajax_loadBDPoll','bd_load_poll');
add_action('wp_ajax_nopriv_loadBDPoll','bd_load_poll');
function bd_poll_vote() {
	global $wpdb;
	$table2_name=$wpdb->prefix."bd_votes";
	$cid = addslashes($_COOKIE['bd_id']);
	$wpdb->query("DELETE FROM $table2_name WHERE cookie_id=\"$cid\"");
	$wpdb->insert($table2_name,array("cookie_id"=>$cid,"vote"=>$_POST['vote']));
	die();
}
add_action('wp_ajax_BDPollVote','bd_poll_vote');
add_action('wp_ajax_nopriv_BDPollVote','bd_poll_vote');
function bd_add_post() {
	$user = 0;
	if($_POST['key']==get_option('user1pass')) {
		$user=1;
	}
	else if($_POST['key']==get_option('user2pass')) {
		$user=2;
	}
	else if($_POST['key']==get_option('user3pass')) {
		$user=3;
	}
	if($user==0) {
		echo 'Incorrect password.';
	}
	else {
		global $wpdb;
		$table1_name=$wpdb->prefix."bd_posts";
		if($wpdb->insert($table1_name,array("user_id"=>$user,"text"=>$_POST['text']))) {
			echo 'success';
		}
		else {
			echo 'Could not add to the database.';
		}
	}
	die();
}
add_action('wp_ajax_nopriv_addBDPost','bd_add_post');
add_action('wp_ajax_addBDPost','bd_add_post');
function bd_mod_login() {
	if($_POST['key']==get_option('user3pass')) {
		echo 'success';
	}
	else {
		echo 'failure';
	}
	die();
}
add_action('wp_ajax_BDModLogin','bd_mod_login');
add_action('wp_ajax_nopriv_BDModLogin','bd_mod_login');
function bd_mod_edit_post() {
	if($_POST['key']==get_option('user3pass')) {
		global $wpdb;
		if($wpdb->update($wpdb->prefix."bd_posts",array('text'=>$_POST['text']),array('id'=>$_POST['bdpostid']))) {
			die('success');
		}
		die('Failed to modify post');
	}
	die('This website is more secure than that!');
}
add_action('wp_ajax_ModEditBDPost','bd_mod_edit_post');
add_action('wp_ajax_nopriv_ModEditBDPost','bd_mod_edit_post');
?>
