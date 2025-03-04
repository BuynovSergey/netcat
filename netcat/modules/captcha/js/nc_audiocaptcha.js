nc_audiocaptcha = function(nc_audiocaptcha_path, nc_audiocaptcha_playlist) {
    this.nc_audiocaptcha_path = nc_audiocaptcha_path;
    this.nc_audiocaptcha_playlist = nc_audiocaptcha_playlist;
};

nc_audiocaptcha.prototype = {
    play: function() {
        var flashvars = {
            'st': this.nc_audiocaptcha_path + 'player/audiocaptcha.txt',
            'pl': this.nc_audiocaptcha_playlist,
            'uid': 'nc_captcha_player'
        };
        var params = {
            id: 'nc_captcha_player',
            bgcolor: '#ffffff',
            allowFullScreen: 'true',
            allowScriptAccess: 'always'
        };
        var attributes = {
            id: 'nc_captcha_player',
            name: 'nc_captcha_player'
        };
        new swfobject.embedSWF(this.nc_audiocaptcha_path + 'player/uppod.swf', 'nc_captcha_player', '0', '0', '9.0.0', false, flashvars, params, attributes);
    }
};