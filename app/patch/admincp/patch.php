<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/
defined('iPHP') OR exit('What are you doing?');
admincp::head();
?>
<style>
#log{color: #999;font-size: 12px;line-height: 22px;}
</style>
<div class="iCMS-container">
  <div class="well iCMS-well iCMS-patch">
    <div id="log"></div>
    <?php if($_GET['do']=="download"){?>
        <div class="form-actions">
        <?php if(isset($_GET['git'])){?>
            <a class="btn btn-success btn-large" href="<?php echo APP_URI; ?>&do=install&release=<?php echo patch::$release; ?>&zipname=<?php echo $_GET['zipname']; ?>&git=true"><i class="fa fa-wrench"></i> 开始升级</a>
        <?php }else{ ?>
            <a class="btn btn-success btn-large" href="<?php echo APP_URI; ?>&do=install"><i class="fa fa-wrench"></i> 开始升级</a>
        <?php } ?>
        </div>
    <?php } ?>
  </div>
</div>
<script type="text/javascript">
var log = "<?php echo $this->msg; ?>".split('<iCMS>');
var n = 0,timer = 0;
setIntervals();

function GoPlay(){
	if (n > log.length-1) {
		n=-1;
		clearIntervals();
	}
	if (n > -1) {
		log_scroll(n);
		n++;
	}
}
function log_scroll(n){
	log_msg(log[n]) ;
    window.scrollTo(0,$(document.body).outerHeight(true));
}
function setIntervals(){
	timer = setInterval('GoPlay()',100);
}
function log_msg(text){
    text = text.replace('#','<hr />');
    document.getElementById('log').innerHTML +=text+'<br /><a name="last"></a>';
}
function clearIntervals(){
	clearInterval(timer);
    <?php if(patch::$upgrade){ ?>
    log_msg('<span class="label label-success">源码升级完成!</span>');
    log_msg('<span class="label label-important">开始升级程序!</span>');
    window.setTimeout(function(){
        window.location.href = '<?php echo APP_URI;?>&do=upgrade';
    },1000);
    <?php } ?>
}
</script>
<?php admincp::foot();?>
