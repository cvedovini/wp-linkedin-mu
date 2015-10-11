<?php
if (!function_exists('find_links')) {
	function find_links($v) {
		$regex = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/i';
		return preg_replace($regex, '<a href="\\0">\\0</a>', $v);
	}
}

if (!empty($updates->values)): ?>
<style>
	.linkedin .updates .share{border-bottom:1px solid #999;margin-bottom:1em;padding-bottom:1em;float:left;clear:both}
	.linkedin .updates .share-content{font-size: smaller;min-height:80px}
	.linkedin .updates .share-title{font-weight:bold}
	</style>

<div class="linkedin"><div class="updates">
<?php foreach ($updates->values as $update):
if (isset($update->updateContent->companyStatusUpdate)) {
	# An update on the company's status or content shared by the company.
	$share = $update->updateContent->companyStatusUpdate->share; ?>
	<div class="share"><?php
		if (isset($share->comment)) echo wpautop(find_links($share->comment));

		if (isset($share->content)) {
			echo '<div class="share-content">';
			if (isset($share->content->thumbnailUrl)) {
				$thumbnail = '<img src="' . $share->content->thumbnailUrl . '" class="alignleft" width="80" />';
			}

			if (isset($share->content->title)) $title = $share->content->title;
			if (isset($share->content->shortenedUrl)) {
				if (isset($title)) $title = '<a href="' . $share->content->shortenedUrl . '">' . $title . '</a>';
				if (isset($thumbnail)) $thumbnail = '<a href="' . $share->content->shortenedUrl . '">' . $thumbnail . '</a>';
			}

			if (isset($thumbnail)) echo $thumbnail;
			echo '<div class="share-prop">';
			if (isset($title)) echo '<div class="share-title">' . $title . '</div>';
			if (isset($share->content->description)) echo wpautop($share->content->description);
			echo '</div></div>';
		}
	?></div><?php
}

if (isset($update->updateContent->companyJobUpdate)) {
	# New job postings on LinkedIn by the specified company.
	$job = $update->updateContent->companyJobUpdate->job; ?>
	<li class="job"><a
		href="<?php echo esc_url($job->siteJobRequest->url); ?>"><?php echo $job->position->title; ?></a> -
		<?php echo $job->locationDescription; ?></li><?php
}
?>
<?php endforeach; ?>
</div></div>
<?php else: ?>
<div class="linkedin"><p class="updates"><?php _e('No updates', 'wp-linkedin-co'); ?></p></div>
<?php endif; ?>

<?php if (LI_DEBUG): ?>
<!--
<?php echo json_encode($updates); ?>
-->
<?php endif; ?>
