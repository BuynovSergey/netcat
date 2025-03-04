(function (params) {
    var block_list = params.block_element.querySelector('div.tpl-block-list');
    block_list.style.position = 'relative';
    var video = document.createElement("video");
    video.loop = true;
    video.muted = true;
    video.autoplay = true;
    if (params.settings.poster) {
        video.poster = params.settings.poster;
    }
    params.settings.video.forEach(function (item) {
        var source = document.createElement("source");
        source.src = item.url;
        source.type = 'video/' + item.url.split('.').pop().toLowerCase();
        video.append(source);
    });
    var bg_video = document.createElement('div');
    bg_video.classList = 'bg-video';
    bg_video.append(video);
    block_list.prepend(bg_video);
});