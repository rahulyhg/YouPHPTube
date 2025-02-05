<?php
global $global, $config;
$isChannel = 1; // still workaround, for gallery-functions, please let it there.
if (!isset($global['systemRootPath'])) {
    require_once '../videos/configuration.php';
}

require_once $global['systemRootPath'] . 'objects/user.php';
require_once $global['systemRootPath'] . 'objects/category.php';
require_once $global['systemRootPath'] . 'objects/subscribe.php';
require_once $global['systemRootPath'] . 'objects/functions.php';

$img = "{$global['webSiteRootURL']}view/img/notfound.jpg";
$poster = "{$global['webSiteRootURL']}view/img/notfound.jpg";
$imgw = 1280;
$imgh = 720;

if (!empty($_GET['type'])) {
    if ($_GET['type'] == 'audio') {
        $_SESSION['type'] = 'audio';
    } else
    if ($_GET['type'] == 'video') {
        $_SESSION['type'] = 'video';
    } else {
        $_SESSION['type'] = "";
        unset($_SESSION['type']);
    }
} else {
    unset($_SESSION['type']);
}
session_write_close();
require_once $global['systemRootPath'] . 'objects/video.php';

$catLink = "";
if (!empty($_GET['catName'])) {
    $catLink = "cat/{$_GET['catName']}/";
}

// add this because if you change the video category the video was not loading anymore
$catName = @$_GET['catName'];

if (empty($_GET['clean_title']) && (isset($advancedCustom->forceCategory) && $advancedCustom->forceCategory === false)) {
    $_GET['catName'] = "";
}

if (empty($video)) {
    $video = Video::getVideo("", "viewable", false, false, true, true);
}

if (empty($video)) {
    $video = Video::getVideo("", "viewable", false, false, false, true);
}
if (empty($video)) {
    $video = YouPHPTubePlugin::getVideo();
}

// allow users to count a view again in case it is refreshed
Video::unsetAddView($video['id']);

// add this because if you change the video category the video was not loading anymore
$_GET['catName'] = $catName;

$_GET['isMediaPlaySite'] = $video['id'];
$obj = new Video("", "", $video['id']);

/*
  if (empty($_SESSION['type'])) {
  $_SESSION['type'] = $video['type'];
  }
 * 
 */
// $resp = $obj->addView();

$get = array('channelName' => @$_GET['channelName'], 'catName' => @$_GET['catName']);

if (!empty($_GET['playlist_id'])) {
    $playlist_id = $_GET['playlist_id'];
    if (!empty($_GET['playlist_index'])) {
        $playlist_index = $_GET['playlist_index'];
    } else {
        $playlist_index = 0;
    }

    $videosArrayId = PlayList::getVideosIdFromPlaylist($_GET['playlist_id']);
    $videosPlayList = Video::getAllVideos("viewable");
    $videosPlayList = PlayList::sortVideos($videosPlayList, $videosArrayId);
    $video = Video::getVideo($videosPlayList[$playlist_index]['id']);
    if (!empty($videosPlayList[$playlist_index + 1])) {
        $autoPlayVideo = Video::getVideo($videosPlayList[$playlist_index + 1]['id']);
        $autoPlayVideo['url'] = $global['webSiteRootURL'] . "playlist/{$playlist_id}/" . ($playlist_index + 1);
    } else if (!empty($videosPlayList[0])) {
        $autoPlayVideo = Video::getVideo($videosPlayList[0]['id']);
        $autoPlayVideo['url'] = $global['webSiteRootURL'] . "playlist/{$playlist_id}/0";
    }

    unset($_GET['playlist_id']);
} else {
    if (!empty($video['next_videos_id'])) {
        $autoPlayVideo = Video::getVideo($video['next_videos_id']);
    } else {
        if ($video['category_order'] == 1) {
            unset($_POST['sort']);
            $category = Category::getAllCategories();
            $_POST['sort']['title'] = "ASC";

            // maybe there's a more slim method?
            $videos = Video::getAllVideos();
            $videoFound = false;
            $autoPlayVideo;
            foreach ($videos as $value) {
                if ($videoFound) {
                    $autoPlayVideo = $value;
                    break;
                }

                if ($value['id'] == $video['id']) {
                    // if the video is found, make another round to have the next video properly.
                    $videoFound = true;
                }
            }
        } else {
            $autoPlayVideo = Video::getRandom($video['id']);
        }
    }

    if (!empty($autoPlayVideo)) {

        $name2 = User::getNameIdentificationById($autoPlayVideo['users_id']);
        $autoPlayVideo['creator'] = '<div class="pull-left"><img src="' . User::getPhoto($autoPlayVideo['users_id']) . '" alt="" class="img img-responsive img-circle zoom" style="max-width: 40px;"/></div><div class="commentDetails" style="margin-left:45px;"><div class="commenterName"><strong>' . $name2 . '</strong> <small>' . humanTiming(strtotime($autoPlayVideo['videoCreation'])) . '</small></div></div>';
        $autoPlayVideo['tags'] = Video::getTags($autoPlayVideo['id']);
        //$autoPlayVideo['url'] = $global['webSiteRootURL'] . $catLink . "video/" . $autoPlayVideo['clean_title'];
        $autoPlayVideo['url'] = Video::getLink($autoPlayVideo['id'], $autoPlayVideo['clean_title'], false, $get);
    }
}

if (!empty($video)) {
    $name = User::getNameIdentificationById($video['users_id']);
    $name = "<a href='" . User::getChannelLink($video['users_id']) . "' class='btn btn-xs btn-default'>{$name}</a>";
    $subscribe = Subscribe::getButton($video['users_id']);
    $video['creator'] = '<div class="pull-left"><img src="' . User::getPhoto($video['users_id']) . '" alt="" class="img img-responsive img-circle zoom" style="max-width: 40px;"/></div><div class="commentDetails" style="margin-left:45px;"><div class="commenterName text-muted"><strong>' . $name . '</strong><br />' . $subscribe . '<br /><small>' . humanTiming(strtotime($video['videoCreation'])) . '</small></div></div>';
    $obj = new Video("", "", $video['id']);

    // dont need because have one embeded video on this page
    // $resp = $obj->addView();
}

if ($video['type'] == "video") {
    $poster = "{$global['webSiteRootURL']}videos/{$video['filename']}.jpg";
} else {
    $poster = "{$global['webSiteRootURL']}view/img/audio_wave.jpg";
}

if (!empty($video)) {
    $source = Video::getSourceFile($video['filename']);
    if (($video['type'] !== "audio") && ($video['type'] !== "linkAudio") && !empty($source['url'])) {
        $img = $source['url'];
        $data = getimgsize($source['path']);
        $imgw = $data[0];
        $imgh = $data[1];
    } else if ($video['type'] == "audio") {
        $img = "{$global['webSiteRootURL']}view/img/audio_wave.jpg";
    }
    $images = Video::getImageFromFilename($video['filename']);
    $poster = $images->poster;
    if (!empty($images->posterPortrait) && basename($images->posterPortrait) !== 'notfound_portrait.jpg') {
        $img = $images->posterPortrait;
        $data = getimgsize($source['path']);
        $imgw = $data[0];
        $imgh = $data[1];
    }
} else {
    $poster = "{$global['webSiteRootURL']}view/img/notfound.jpg";
}

$objSecure = YouPHPTubePlugin::getObjectDataIfEnabled('SecureVideosDirectory');

if (!empty($autoPlayVideo)) {
    $autoPlaySources = getSources($autoPlayVideo['filename'], true);
    $autoPlayURL = $autoPlayVideo['url'];
    $autoPlayPoster = "{$global['webSiteRootURL']}videos/{$autoPlayVideo['filename']}.jpg";
    $autoPlayThumbsSprit = "{$global['webSiteRootURL']}videos/{$autoPlayVideo['filename']}_thumbsSprit.jpg";
} else {
    $autoPlaySources = array();
    $autoPlayURL = '';
    $autoPlayPoster = '';
    $autoPlayThumbsSprit = "";
}

if (empty($_GET['videoName'])) {
    $_GET['videoName'] = $video['clean_title'];
}

$v = Video::getVideoFromCleanTitle($_GET['videoName']);


YouPHPTubePlugin::getModeYouTube($v['id']);
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
    <head>
        <title><?php echo $video['title']; ?> - <?php echo $config->getWebSiteTitle(); ?></title>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/js/video.js/video-js.min.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/css/player.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/css/social.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/js/jquery-ui/jquery-ui.min.css" rel="stylesheet" type="text/css"/>
        <?php include $global['systemRootPath'] . 'view/include/head.php'; ?>
        <link rel="image_src" href="<?php echo $img; ?>" />
        <meta property="fb:app_id"             content="774958212660408" />
        <meta property="og:url"                content="<?php echo $global['webSiteRootURL'], $catLink, "video/", $video['clean_title']; ?>" />
        <meta property="og:type"               content="video.other" />
        <meta property="og:title"              content="<?php echo str_replace('"', '', $video['title']); ?> - <?php echo $config->getWebSiteTitle(); ?>" />
        <meta property="og:description"        content="<?php echo!empty($custom) ? $custom : str_replace('"', '', $video['title']); ?>" />
        <meta property="og:image"              content="<?php echo $img; ?>" />
        <meta property="og:image:width"        content="<?php echo $imgw; ?>" />
        <meta property="og:image:height"       content="<?php echo $imgh; ?>" />
        <meta property="video:duration" content="<?php echo Video::getItemDurationSeconds($video['duration']); ?>"  />
        <meta property="duration" content="<?php echo Video::getItemDurationSeconds($video['duration']); ?>"  />
    </head>

    <body class="<?php echo $global['bodyClass']; ?>">
        <?php include $global['systemRootPath'] . 'view/include/navbar.php'; ?>
        <?php
        if (!empty($advancedCustomUser->showChannelBannerOnModeYoutube)) {
            ?>
            <div class="container" style="margin-bottom: 10px;">
                <img src="<?php echo User::getBackground($video['users_id']); ?>" class="img img-responsive" />
            </div>
            <?php
        }
        ?>
        <div class="container-fluid principalContainer" itemscope itemtype="http://schema.org/VideoObject">
            <?php
            if (!empty($video)) {
                if (empty($video['type'])) {
                    $video['type'] = "video";
                }
                $img_portrait = ($video['rotation'] === "90" || $video['rotation'] === "270") ? "img-portrait" : "";
                if (!empty($advancedCustom->showAdsenseBannerOnTop)) {
                    ?>
                    <style>
                        .compress {
                            top: 100px !important;
                        }
                    </style>
                    <div class="row">
                        <div class="col-lg-12 col-sm-12 col-xs-12">
                            <center style="margin:5px;">
                                <?php
                                echo $config->getAdsense();
                                ?>
                            </center>
                        </div>
                    </div>
                    <?php
                }
                $vType = $video['type'];
                if ($vType == "linkVideo") {
                    $vType = "video";
                } else if ($vType == "live") {
                    $vType = "../../plugin/Live/view/liveVideo";
                } else if ($vType == "linkAudio") {
                    $vType = "audio";
                }
                require "{$global['systemRootPath']}view/include/{$vType}.php";
                ?>
                <div class="row" id="modeYoutubeBottom">
                    <div class="col-sm-1 col-md-1"></div>
                    <div class="col-sm-6 col-md-6">
                        <div class="row bgWhite list-group-item">
                            <div class="row divMainVideo">
                                <div class="col-xs-4 col-sm-4 col-md-4">
                                    <img src="<?php echo $img; ?>" alt="<?php echo str_replace('"', '', $video['title']); ?>" class="img img-responsive <?php echo $img_portrait; ?> rotate<?php echo $video['rotation']; ?>" height="130" itemprop="thumbnail" />
                                    <time class="duration" itemprop="duration" datetime="<?php echo Video::getItemPropDuration($video['duration']); ?>" ><?php echo Video::getCleanDuration($video['duration']); ?></time>
                                    <meta itemprop="thumbnailUrl" content="<?php echo $img; ?>" />
                                    <meta itemprop="contentURL" content="<?php echo Video::getLink($video['id'], $video['clean_title']); ?>" />
                                    <meta itemprop="embedURL" content="<?php echo Video::getLink($video['id'], $video['clean_title'], true); ?>" />
                                    <meta itemprop="uploadDate" content="<?php echo $video['created']; ?>" />
                                    <meta itemprop="description" content="<?php echo str_replace('"', '', $video['title']); ?> - <?php echo htmlentities($video['description']); ?>" />

                                </div>
                                <div class="col-xs-8 col-sm-8 col-md-8">
                                    <h1 itemprop="name">
                                        <?php
                                        echo $video['title'];
                                        if (Video::canEdit($video['id'])) {
                                            ?>
                                            <a href="<?php echo $global['webSiteRootURL']; ?>mvideos?video_id=<?php echo $video['id']; ?>" class="btn btn-primary btn-xs" data-toggle="tooltip" title="<?php echo __("Edit Video"); ?>"><i class="fa fa-edit"></i> <?php echo __("Edit Video"); ?></a>
                                        <?php } ?>
                                        <small>
                                            <?php
                                            if (!empty($video['id'])) {
                                                $video['tags'] = Video::getTags($video['id']);
                                            } else {
                                                $video['tags'] = array();
                                            }
                                            foreach ($video['tags'] as $value) {
                                                if ($value->label === __("Group")) {
                                                    ?>
                                                    <span class="label label-<?php echo $value->type; ?>"><?php echo $value->text; ?></span>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </small>
                                    </h1>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <?php echo $video['creator']; ?>
                                    </div>

                                    <?php
                                    if (empty($advancedCustom->doNotDisplayViews)) {
                                        ?> 
                                        <span class="watch-view-count pull-right text-muted" itemprop="interactionCount"><span class="view-count<?php echo $video['id']; ?>"><?php echo number_format($video['views_count'], 0); ?></span> <?php echo __("Views"); ?></span>
                                        <?php
                                    }
                                    ?>
                                    <?php
                                    if (YouPHPTubePlugin::isEnabledByName("VideoTags")) {
                                        echo VideoTags::getLabels($video['id'], false);
                                    }
                                    ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 watch8-action-buttons text-muted">
                                    <?php if (empty($advancedCustom->disableShareAndPlaylist)) { ?>
                                        <?php if (CustomizeUser::canShareVideosFromVideo($video['id'])) { ?>
                                            <a href="#" class="btn btn-default no-outline" id="shareBtn">
                                                <span class="fa fa-share"></span> <?php echo __("Share"); ?>
                                            </a>
                                            <?php
                                        }

                                        if (CustomizeUser::canDownloadVideosFromVideo($video['id'])) {
                                            ?>
                                            <a href="#" class="btn btn-default no-outline" id="downloadBtn">
                                                <span class="fa fa-download"></span> <?php echo __("Download"); ?>
                                            </a>
                                            <?php
                                        }
                                        ?>
                                    <?php } echo YouPHPTubePlugin::getWatchActionButton($video['id']); ?>
                                    <?php
                                    if (empty($advancedCustom->removeThumbsUpAndDown)) {
                                        ?>
                                        <a href="#" class="btn btn-default no-outline pull-right <?php echo ($video['myVote'] == - 1) ? "myVote" : "" ?>" id="dislikeBtn" <?php if (!User::isLogged()) { ?> data-toggle="tooltip" title="<?php echo __("DonÂ´t like this video? Sign in to make your opinion count."); ?>" <?php } ?>>
                                            <span class="fa fa-thumbs-down"></span> <small><?php echo $video['dislikes']; ?></small>
                                        </a>
                                        <a href="#" class="btn btn-default no-outline pull-right <?php echo ($video['myVote'] == 1) ? "myVote" : "" ?>" id="likeBtn" <?php if (!User::isLogged()) { ?> data-toggle="tooltip" title="<?php echo __("Like this video? Sign in to make your opinion count."); ?>" <?php } ?>>
                                            <span class="fa fa-thumbs-up"></span>
                                            <small><?php echo $video['likes']; ?></small>
                                        </a>
                                        <script>
                                            $(document).ready(function () {
        <?php if (User::isLogged()) { ?>
                                                    $("#dislikeBtn, #likeBtn").click(function () {
                                                        $.ajax({
                                                            url: '<?php echo $global['webSiteRootURL']; ?>' + ($(this).attr("id") == "dislikeBtn" ? "dislike" : "like"),
                                                            method: 'POST',
                                                            data: {'videos_id': <?php echo $video['id']; ?>},
                                                            success: function (response) {
                                                                $("#likeBtn, #dislikeBtn").removeClass("myVote");
                                                                if (response.myVote == 1) {
                                                                    $("#likeBtn").addClass("myVote");
                                                                } else if (response.myVote == -1) {
                                                                    $("#dislikeBtn").addClass("myVote");
                                                                }
                                                                $("#likeBtn small").text(response.likes);
                                                                $("#dislikeBtn small").text(response.dislikes);
                                                            }
                                                        });
                                                        return false;
                                                    });
        <?php } else { ?>
                                                    $("#dislikeBtn, #likeBtn").click(function () {
                                                        $(this).tooltip("show");
                                                        return false;
                                                    });
        <?php } ?>
                                            });
                                        </script>

                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <?php if (CustomizeUser::canDownloadVideosFromVideo($video['id'])) { ?>
                            <div class="row bgWhite list-group-item menusDiv" id="downloadDiv">
                                <div class="tabbable-panel">
                                    <div class="list-group">
                                        <?php
                                        $files = getVideosURL($video['filename']);
                                        foreach ($files as $key => $theLink) {
                                            if (empty($advancedCustom->showImageDownloadOption)) {
                                                if ($key == "jpg" || $key == "gif") {
                                                    continue;
                                                }
                                            }
                                            if (strpos($theLink['url'], '?') === false) {
                                                $theLink['url'] .= "?download=1&title=" . urlencode($video['title'] . "_{$key}_.mp4");
                                            }
                                            ?>
                                            <a href="<?php echo $theLink['url']; ?>" class="list-group-item list-group-item-action" target="_blank">
                                                <i class="fas fa-download"></i> <?php echo $key; ?>
                                            </a>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <script>
                                $(document).ready(function () {
                                    $("#downloadDiv").slideUp();
                                    $("#downloadBtn").click(function () {
                                        $(".menusDiv").not("#downloadDiv").slideUp();
                                        $("#downloadDiv").slideToggle();
                                        return false;
                                    });
                                });
                            </script>
                        <?php } ?>
                        <?php if (CustomizeUser::canShareVideosFromVideo($video['id'])) { ?>
                            <div class="row bgWhite list-group-item menusDiv" id="shareDiv">
                                <div class="tabbable-panel">
                                    <div class="tabbable-line text-muted">
                                        <ul class="nav nav-tabs">
                                            <li class="nav-item">
                                                <a class="nav-link " href="#tabShare" data-toggle="tab">
                                                    <span class="fa fa-share"></span>
                                                    <?php echo __("Share"); ?>
                                                </a>
                                            </li>

                                            <?php
                                            if (empty($objSecure->disableEmbedMode)) {
                                                ?>
                                                <li class="nav-item">
                                                    <a class="nav-link " href="#tabEmbed" data-toggle="tab">
                                                        <span class="fa fa-code"></span>
                                                        <?php echo __("Embed"); ?>
                                                    </a>
                                                </li>
                                                <?php
                                            }
                                            ?>

                                            <li class="nav-item">
                                                <a class="nav-link" href="#tabEmail" data-toggle="tab">
                                                    <span class="fa fa-envelope"></span>
                                                    <?php echo __("E-mail"); ?>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" href="#tabPermaLink" data-toggle="tab">
                                                    <span class="fa fa-link"></span>
                                                    <?php echo __("Permanent Link"); ?>
                                                </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content clearfix">
                                            <div class="tab-pane active" id="tabShare">
                                                <?php
                                                $url = urlencode($global['webSiteRootURL'] . "{$catLink}video/" . $video['clean_title']);
                                                $title = urlencode($video['title']);
                                                include $global['systemRootPath'] . 'view/include/social.php';
                                                ?>
                                            </div>
                                            <div class="tab-pane" id="tabEmbed">
                                                <h4><span class="glyphicon glyphicon-share"></span> <?php echo __("Share Video"); ?>:</h4>
                                                <textarea class="form-control" style="min-width: 100%" rows="5" id="textAreaEmbed" readonly="readonly"><?php
                                                    if ($video['type'] == 'video' || $video['type'] == 'embed') {
                                                        $code = '<iframe width="640" height="360" style="max-width: 100%;max-height: 100%; border:none;" src="' . Video::getLink($video['id'], $video['clean_title'], true) . '" frameborder="0" allowfullscreen="allowfullscreen" allow="autoplay" scrolling="no">iFrame is not supported!</iframe>';
                                                    } else {
                                                        $code = '<iframe width="350" height="40" style="max-width: 100%;max-height: 100%; border:none;" src="' . Video::getLink($video['id'], $video['clean_title'], true) . '" frameborder="0" allowfullscreen="allowfullscreen" allow="autoplay" scrolling="no">iFrame is not supported!</iframe>';
                                                    }
                                                    echo htmlentities($code);
                                                    ?>
                                                </textarea>
                                            </div>
                                            <div class="tab-pane" id="tabEmail">
                                                <?php if (!User::isLogged()) { ?>
                                                    <strong>
                                                        <a href="<?php echo $global['webSiteRootURL']; ?>user"><?php echo __("Sign in now!"); ?></a>
                                                    </strong>
                                                <?php } else { ?>
                                                    <form class="well form-horizontal" action="<?php echo $global['webSiteRootURL']; ?>sendEmail" method="post"  id="contact_form">
                                                        <fieldset>
                                                            <!-- Text input-->
                                                            <div class="form-group">
                                                                <label class="col-md-4 control-label"><?php echo __("E-mail"); ?></label>
                                                                <div class="col-md-8 inputGroupContainer">
                                                                    <div class="input-group">
                                                                        <span class="input-group-addon"><i class="glyphicon glyphicon-envelope"></i></span>
                                                                        <input name="email" placeholder="<?php echo __("E-mail Address"); ?>" class="form-control"  type="text">
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Text area -->

                                                            <div class="form-group">
                                                                <label class="col-md-4 control-label"><?php echo __("Message"); ?></label>
                                                                <div class="col-md-8 inputGroupContainer">
                                                                    <div class="input-group">
                                                                        <span class="input-group-addon"><i class="glyphicon glyphicon-pencil"></i></span>
                                                                        <textarea class="form-control" name="comment" placeholder="<?php echo __("Message"); ?>"><?php echo __("I would like to share this video with you:"); ?> <?php echo Video::getLink($video['id'], $video['clean_title']); ?></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-md-4 control-label"><?php echo __("Type the code"); ?></label>
                                                                <div class="col-md-8 inputGroupContainer">
                                                                    <div class="input-group">
                                                                        <span class="input-group-addon"><img src="<?php echo $global['webSiteRootURL']; ?>captcha" id="captcha"></span>
                                                                        <span class="input-group-addon"><span class="btn btn-xs btn-success" id="btnReloadCapcha"><span class="glyphicon glyphicon-refresh"></span></span></span>
                                                                        <input name="captcha" placeholder="<?php echo __("Type the code"); ?>" class="form-control" type="text" style="height: 60px;" maxlength="5" id="captchaText">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- Button -->
                                                            <div class="form-group">
                                                                <label class="col-md-4 control-label"></label>
                                                                <div class="col-md-8">
                                                                    <button type="submit" class="btn btn-primary" ><?php echo __("Send"); ?> <span class="glyphicon glyphicon-send"></span></button>
                                                                </div>
                                                            </div>

                                                        </fieldset>
                                                    </form>
                                                    <script>
                                                        $(document).ready(function () {
                                                            $('#btnReloadCapcha').click(function () {
                                                                $('#captcha').attr('src', '<?php echo $global['webSiteRootURL']; ?>captcha?' + Math.random());
                                                                $('#captchaText').val('');
                                                            });
                                                            $('#contact_form').submit(function (evt) {
                                                                evt.preventDefault();
                                                                modal.showPleaseWait();
                                                                $.ajax({
                                                                    url: '<?php echo $global['webSiteRootURL']; ?>objects/sendEmail.json.php',
                                                                    data: $('#contact_form').serializeArray(),
                                                                    type: 'post',
                                                                    success: function (response) {
                                                                        modal.hidePleaseWait();
                                                                        if (!response.error) {
                                                                            swal("<?php echo __("Congratulations!"); ?>", "<?php echo __("Your message has been sent!"); ?>", "success");
                                                                        } else {
                                                                            swal("<?php echo __("Your message could not be sent!"); ?>", response.error, "error");
                                                                        }
                                                                        $('#btnReloadCapcha').trigger('click');
                                                                    }
                                                                });
                                                                return false;
                                                            });
                                                        });
                                                    </script>
                                                <?php } ?>
                                            </div>

                                            <div class="tab-pane" id="tabPermaLink">
                                                <div class="form-group">
                                                    <label class="control-label"><?php echo __("Permanent Link") ?></label>
                                                    <div class="">
                                                        <input value="<?php echo Video::getPermaLink($video['id']); ?>" class="form-control" readonly="readonly"  id="linkPermanent"/>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label"><?php echo __("URL Friendly") ?> (SEO)</label>
                                                    <div class="">
                                                        <input value="<?php echo Video::getURLFriendly($video['id']); ?>" class="form-control" readonly="readonly" id="linkFriendly"/>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label"><?php echo __("Current Time") ?> (SEO)</label>
                                                    <div class="">
                                                        <input value="<?php echo Video::getURLFriendly($video['id']); ?>?t=0" class="form-control" readonly="readonly" id="linkCurrentTime"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="row bgWhite list-group-item">
                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-lg-12">
                                    <div class="col-xs-4 col-sm-2 col-lg-2 text-right"><strong><?php echo __("Category"); ?>:</strong></div>
                                    <div class="col-xs-8 col-sm-10 col-lg-10"><a class="btn btn-xs btn-default"  href="<?php echo $global['webSiteRootURL']; ?>cat/<?php echo $video['clean_category']; ?>"><span class="<?php echo $video['iconClass']; ?>"></span> <?php echo $video['category']; ?></a></div>
                                    <?php
                                    if (!empty($video['rrating'])) {
                                        ?>
                                        <div class="col-xs-4 col-sm-2 col-lg-2 text-right"><strong><?php echo __("Rating"); ?>:</strong></div>
                                        <div class="col-xs-8 col-sm-10 col-lg-10">
                                            <?php
                                            include $global['systemRootPath'] . 'view/rrating/rating-' . $video['rrating'] . '.php';
                                            ?>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                    <div class="col-xs-4 col-sm-2 col-lg-2 text-right"><strong><?php echo __("Description"); ?>:</strong></div>
                                    <div class="col-xs-8 col-sm-10 col-lg-10" itemprop="description"><?php echo nl2br(textToLink(htmlentities($video['description']))); ?></div>
                                </div>
                            </div>

                        </div>
                        <script>
                            $(document).ready(function () {
                                $("#shareDiv").slideUp();
                                $("#shareBtn").click(function () {
                                    $(".menusDiv").not("#shareDiv").slideUp();
                                    $("#shareDiv").slideToggle();
                                    return false;
                                });
                            });
                        </script>
                        <div class="row bgWhite list-group-item">
                            <?php include $global['systemRootPath'] . 'view/videoComments.php'; ?>
                        </div>
                    </div>
                    <div class="col-sm-4 col-md-4 bgWhite list-group-item rightBar">
                        <?php
                        if (!empty($advancedCustom->showAdsenseBannerOnLeft)) {
                            ?>
                            <div class="col-lg-12 col-sm-12 col-xs-12">
                                <?php echo $config->getAdsense(); ?>
                            </div>
                            <?php
                        }
                        if (!empty($playlist_id)) {
                            include $global['systemRootPath'] . 'view/include/playlist.php';
                            ?>
                            <script>
                                $(document).ready(function () {
                                    Cookies.set('autoplay', true, {
                                        path: '/',
                                        expires: 365
                                    });
                                });
                            </script>
                        <?php } else if (empty($autoPlayVideo)) {
                            ?>
                            <div class="col-lg-12 col-sm-12 col-xs-12 autoplay text-muted" >
                                <strong><?php echo __("Autoplay ended"); ?></strong>
                                <span class="pull-right">
                                    <span><?php echo __("Autoplay"); ?></span>
                                    <span>
                                        <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="bottom"  title="<?php echo __("When autoplay is enabled, a suggested video will automatically play next."); ?>"></i>
                                    </span>
                                    <div class="material-switch pull-right">
                                        <input type="checkbox" class="saveCookie" name="autoplay" id="autoplay">
                                        <label for="autoplay" class="label-primary"></label>
                                    </div>
                                </span>
                            </div>
                        <?php } else if (!empty($autoPlayVideo)) { ?>
                            <div class="row">
                                <div class="col-lg-12 col-sm-12 col-xs-12 autoplay text-muted">
                                    <strong><?php echo __("Up Next"); ?></strong>
                                    <span class="pull-right">
                                        <span><?php echo __("Autoplay"); ?></span>
                                        <span>
                                            <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="bottom"  title="<?php echo __("When autoplay is enabled, a suggested video will automatically play next."); ?>"></i>
                                        </span>
                                        <div class="material-switch pull-right">
                                            <input type="checkbox" class="saveCookie" name="autoplay" id="autoplay">
                                            <label for="autoplay" class="label-primary"></label>
                                        </div>
                                    </span>
                                </div>
                            </div>
                            <div class="col-lg-12 col-sm-12 col-xs-12 bottom-border autoPlayVideo" id="autoPlayVideoDiv" itemscope itemtype="http://schema.org/VideoObject" >
                                <a href="<?php echo Video::getLink($autoPlayVideo['id'], $autoPlayVideo['clean_title'], "", $get); ?>" title="<?php echo str_replace('"', '', $autoPlayVideo['title']); ?>" class="videoLink h6">
                                    <div class="col-lg-5 col-sm-5 col-xs-5 nopadding thumbsImage">
                                        <?php
                                        $imgGif = "";
                                        if (file_exists("{$global['systemRootPath']}videos/{$autoPlayVideo['filename']}.gif")) {
                                            $imgGif = "{$global['webSiteRootURL']}videos/{$autoPlayVideo['filename']}.gif";
                                        }
                                        if (($autoPlayVideo['type'] !== "audio") && ($autoPlayVideo['type'] !== "linkAudio")) {
                                            $img = "{$global['webSiteRootURL']}videos/{$autoPlayVideo['filename']}.jpg";
                                            $img_portrait = ($autoPlayVideo['rotation'] === "90" || $autoPlayVideo['rotation'] === "270") ? "img-portrait" : "";
                                        } else {
                                            $img = "{$global['webSiteRootURL']}view/img/audio_wave.jpg";
                                            $img_portrait = "";
                                        }
                                        ?>
                                        <img src="<?php echo $img; ?>" alt="<?php echo str_replace('"', '', $autoPlayVideo['title']); ?>" class="img-responsive <?php echo $img_portrait; ?>  rotate<?php echo $autoPlayVideo['rotation']; ?>" height="130" itemprop="thumbnail" />
                                        <?php if (!empty($imgGif)) { ?>
                                            <img src="<?php echo $imgGif; ?>" style="position: absolute; top: 0; display: none;" alt="<?php echo str_replace('"', '', $autoPlayVideo['title']); ?>" id="thumbsGIF<?php echo $autoPlayVideo['id']; ?>" class="thumbsGIF img-responsive <?php echo $img_portrait; ?>  rotate<?php echo $autoPlayVideo['rotation']; ?>" height="130" />
                                        <?php } ?>
                                        <meta itemprop="thumbnailUrl" content="<?php echo $img; ?>" />
                                        <meta itemprop="contentURL" content="<?php echo Video::getLink($autoPlayVideo['id'], $autoPlayVideo['clean_title']); ?>" />
                                        <meta itemprop="embedURL" content="<?php echo Video::getLink($autoPlayVideo['id'], $autoPlayVideo['clean_title'], true); ?>" />
                                        <meta itemprop="uploadDate" content="<?php echo $autoPlayVideo['created']; ?>" />
                                        <time class="duration" itemprop="duration" datetime="<?php echo Video::getItemPropDuration($autoPlayVideo['duration']); ?>"><?php echo Video::getCleanDuration($autoPlayVideo['duration']); ?></time>
                                    </div>
                                    <div class="col-lg-7 col-sm-7 col-xs-7 videosDetails">
                                        <div class="text-uppercase row"><strong itemprop="name" class="title"><?php echo $autoPlayVideo['title']; ?></strong></div>
                                        <div class="details row text-muted" itemprop="description">
                                            <div>
                                                <strong><?php echo __("Category"); ?>: </strong>
                                                <span class="<?php echo $autoPlayVideo['iconClass']; ?>"></span>
                                                <?php echo $autoPlayVideo['category']; ?>
                                            </div>

                                            <?php
                                            if (empty($advancedCustom->doNotDisplayViews)) {
                                                ?> 
                                                <div>
                                                    <strong class=""><?php echo number_format($autoPlayVideo['views_count'], 0); ?></strong>
                                                    <?php echo __("Views"); ?>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                            <div><?php echo $autoPlayVideo['creator']; ?></div>
                                        </div>
                                        <div class="row">
                                            <?php
                                            if (!empty($autoPlayVideo['tags'])) {
                                                foreach ($autoPlayVideo['tags'] as $autoPlayVideo2) {
                                                    if ($autoPlayVideo2->label === __("Group")) {
                                                        ?>
                                                        <span class="label label-<?php echo $autoPlayVideo2->type; ?>"><?php echo $autoPlayVideo2->text; ?></span>
                                                        <?php
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php } ?>
                        <div class="col-lg-12 col-sm-12 col-xs-12 extraVideos nopadding"></div>
                        <!-- videos List -->
                        <div id="videosList">
                            <?php include $global['systemRootPath'] . 'view/videosList.php'; ?>
                        </div>
                        <!-- End of videos List -->

                        <script>
                            var fading = false;
                            var autoPlaySources = <?php echo json_encode($autoPlaySources); ?>;
                            var autoPlayURL = '<?php echo $autoPlayURL; ?>';
                            var autoPlayPoster = '<?php echo $autoPlayPoster; ?>';
                            var autoPlayThumbsSprit = '<?php echo $autoPlayThumbsSprit; ?>';

                            function showAutoPlayVideoDiv() {
                                var auto = $("#autoplay").prop('checked');
                                if (!auto) {
                                    $('#autoPlayVideoDiv').slideUp();
                                } else {
                                    $('#autoPlayVideoDiv').slideDown();
                                }
                            }
                            $(document).ready(function () {
                                $("input.saveCookie").each(function () {
                                    var mycookie = Cookies.get($(this).attr('name'));
                                    if (mycookie && mycookie == "true") {
                                        $(this).prop('checked', mycookie);
                                    }
                                });
                                $("input.saveCookie").change(function () {
                                    var auto = $(this).prop('checked');
                                    Cookies.set($(this).attr("name"), auto, {
                                        path: '/',
                                        expires: 365
                                    });
                                });
                                $("#autoplay").change(function () {
                                    showAutoPlayVideoDiv();
                                });
                                showAutoPlayVideoDiv();
                            });
                        </script>
                    </div>
                    <div class="col-sm-1 col-md-1"></div>
                </div>
            <?php } else { ?>
                    <br>
                    <br>
                    <br>
                    <br>
                <div class="alert alert-warning">
                    <span class="glyphicon glyphicon-facetime-video"></span> <strong><?php echo __("Attention"); ?>!</strong> <?php echo empty($advancedCustom->videoNotFoundText->value)?__("We have not found any videos or audios to show"):$advancedCustom->videoNotFoundText->value; ?>.
                </div>
            <?php } ?>
        </div>
        <script src="<?php echo $global['webSiteRootURL']; ?>view/js/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
        <script>
                            /*** Handle jQuery plugin naming conflict between jQuery UI and Bootstrap ***/
                            $.widget.bridge('uibutton', $.ui.button);
                            $.widget.bridge('uitooltip', $.ui.tooltip);
        </script>
        <?php
        $videoJSArray = array("view/js/video.js/video.js");
        if ($advancedCustom != false) {
            $disableYoutubeIntegration = $advancedCustom->disableYoutubePlayerIntegration;
        } else {
            $disableYoutubeIntegration = false;
        }

        if ((isset($_GET['isEmbedded'])) && ($disableYoutubeIntegration == false)) {
            if ($_GET['isEmbedded'] == "y") {
                $videoJSArray[] = "view/js/videojs-youtube/Youtube.js";
            } else if ($_GET['isEmbedded'] == "v") {
                $videoJSArray[] = "view/js/videojs-vimeo/videojs-vimeo.js";
            }
        }
        $jsURL = combineFiles($videoJSArray, "js");
        ?>
        <script src="<?php echo $jsURL; ?>" type="text/javascript"></script>
        <?php
        include $global['systemRootPath'] . 'view/include/footer.php';
        $videoJSArray = array(
            "view/js/videojs-persistvolume/videojs.persistvolume.js",
            "view/js/BootstrapMenu.min.js");
        $jsURL = combineFiles($videoJSArray, "js");
        ?>
        <script src="<?php echo $jsURL; ?>" type="text/javascript"></script>
        <script>
                            var fading = false;
                            var autoPlaySources = <?php echo json_encode($autoPlaySources); ?>;
                            var autoPlayURL = '<?php echo $autoPlayURL; ?>';
                            var autoPlayPoster = '<?php echo $autoPlayPoster; ?>';
                            var autoPlayThumbsSprit = '<?php echo $autoPlayThumbsSprit; ?>';

                            $(document).ready(function () {
                            });
        </script>
    </body>
</html>
<?php include $global['systemRootPath'] . 'objects/include_end.php'; ?>
