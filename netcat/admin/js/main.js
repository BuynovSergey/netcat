$nc(document).ready(function() {

    function isTouch()
    {
        if (navigator.userAgent.indexOf("iPhone") != -1)
        {
            return true;
        }

        if (navigator.userAgent.indexOf("iPad") != -1)
        {
            return true;
        }

        if (navigator.userAgent.indexOf("iPod") != -1)
        {
            return true;
        }

        var userag = navigator.userAgent.toLowerCase();
        var isAndroid = userag.indexOf("android") > -1;
        if(isAndroid) {
            return true;
        }
        return false;
    }

    if (isTouch())
    {
        $nc('.nc-navbar .nc-tray').css('margin-right', '0px');
    }

    function resize_layout() {
        var demoPanelOffset = $nc('.nc-demo-panel').length ? $nc('.nc-demo-panel').outerHeight() : 0;

        if ( $nc('#mainViewContent').hasClass('fullscreen') ) {

            $nc('#mainViewContent').offset({top:0,left:0});
            var sizes = {width:$nc(window).width()+'px', height:$nc(window).height()+'px',zIndex:15};
            $nc('#mainViewContent').css(sizes);

            $nc('#mainViewContent iframe').css(sizes);
            $nc('.nc-navbar').css({position:'static'});
            return;
        }

        $nc('#mainViewContent').css({top:0,left:0,position:'static',width:'auto'});

        $nc('.middle').css({
                height: $nc(window).height()  - $nc('.nc-navbar').height() - demoPanelOffset + 'px'
        });
        $nc('.middle .middle_left iframe').css({
                height: $nc(window).height()  - $nc('.nc-navbar').height() - $nc('#tree_mode_name').outerHeight() - demoPanelOffset + 'px'
        });


        $nc('.nc-navbar').css({position:'relative'});

        var content_height = $nc(window).height() - $nc('.nc-navbar').outerHeight() - $nc('.header_block').outerHeight() - demoPanelOffset;
        if ($nc('.clear_footer').is(':visible')) {
            content_height -= $nc('.clear_footer').outerHeight();
        }

        $nc('.content_block').height(content_height);
        $nc('.content_block iframe').css({
                height:content_height+'px',
                width:'100%'
        });

        generateSlider1();
        generateSlider2();
    }


    $nc(window).resize(resize_layout);

    $nc('.content_block iframe').load(function() {
        resize_layout();
    });

    window.resize_layout = resize_layout;

    //--------------------------------------------------------------------------
    // Слайдбар левой панели
    //--------------------------------------------------------------------------

    startScroller        = 0,
    pageStartScroller    = 0,
    scroller1ClickObject = 0,
    scrollerOffset       = 0,
    newWidth             = 0,
    storedWidth          = 0,
    minWidth             = 259,
    maxWidth             = 600;
    doubleClickInterval  = 300; // ms
    isDoubleClick        = false;

    var getEventX = function(e) {
            if (isTouch()) {
                e.preventDefault();
                var touch = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
                x = touch.pageX;
            } else {
                x = e.pageX;
            }
        return x;
    }

    scrollerSetCookieX = function(x) {
        $nc.cookie('SCROLLER_X', x, { expires: 30, path: '/' });
    };

    scrollerGetCookieX = function() {
        if ($nc.cookie('SCROLLER_X') != 'undefined') {
            return Math.round($nc.cookie('SCROLLER_X'));
        } else {
            return '';
        }
    };

    scrollerMouseDown = function(e) {
        $nc(document).unbind('mousemove');
        $nc(document).unbind('mouseup');
        $nc(document).unbind('touchmove');
        $nc(document).unbind('touchend');

        pageStartScroller = getEventX(e);

        startScroller = $nc('.middle_left').width() + 1;

        scrollerOffset = pageStartScroller - $nc('.middle_left').width() - 2;

        $nc('.menu_left_opacity, .menu_right_opacity').show();

        if (isTouch()) {
            $nc(document).bind('touchmove', scrollerMouseMove);
            $nc(document).bind('touchend', scrollerMouseUp);
        } else {
            $nc(document).bind('mousemove', scrollerMouseMove);
            $nc(document).bind('mouseup', scrollerMouseUp);
        }

        var itemHeight = $nc('body>.middle').height();
        var itemTop = $nc('body>.nc-navbar').height();

        $nc(this).css({visibility:'hidden'}).addClass('middle_border_original');
        var slider_overlay = $nc('<div class="slider_overlay"><div class="bg"></div><div class="middle_border middle_border_clone"></div></div>');
        slider_overlay.css({
                height:itemHeight+'px',
                left:(pageStartScroller - scrollerOffset - maxWidth)+'px',
                top:itemTop+'px'
        });

        $nc('.middle_border_clone', slider_overlay).css({
            backgroundPosition: 'center '+( (itemHeight - $nc('.nc_footer').height()) / 2 - 17)+'px'
        });

        $nc('.bg', slider_overlay).css({height:itemHeight+'px'});

        // отменить перенос и выделение текста при клике на тексте
        document.ondragstart = function() { return false }
        document.body.onselectstart = function() { return false }

        $nc(document.body).append(slider_overlay);
    }

    scrollerMouseMove = function(e) {
        var x = getEventX(e);
        e.preventDefault();
        x -= scrollerOffset;
        x = (x <= minWidth ? minWidth : (x >= maxWidth ? maxWidth : x));
        $nc('.slider_overlay').css({left:(x-maxWidth)+'px'});
        return false;
    }

    scrollerMouseUp = function(e) {
        $nc(document).unbind('mousemove');
        $nc(document).unbind('mouseup');
        $nc(document).unbind('touchmove');
        $nc(document).unbind('touchend');

        var x = getEventX(e);

        // Сворачиваем панель при 2xClick
        if (isDoubleClick) {
            storedWidth = x;
            newWidth    = 0;
        }
        else {
            isDoubleClick = true;
            setTimeout(function(){isDoubleClick=false}, doubleClickInterval);

            x = storedWidth ? storedWidth : x;
            storedWidth = 0;
            newWidth = x - pageStartScroller + startScroller;

            if (newWidth <= minWidth) {
                newWidth = minWidth;
            } else if (newWidth >= maxWidth) {
                newWidth = maxWidth;
            }
        }

        $nc('.middle_left').css('width', (newWidth - 1) + 'px');
        $nc('.middle_right').css('margin-left', newWidth + 'px');
        scrollerSetCookieX(newWidth);

        $nc('.menu_left_opacity, .menu_right_opacity').hide();
        generateSlider1();
        generateSlider2();
        $nc('.slider_overlay').remove();
        $nc('.middle_border_original').css({visibility:'visible'});
        document.ondragstart        = null;
        document.body.onselectstart = null;
    }

    scrollerOnShow = function() {
        cookieX = scrollerGetCookieX();
        if (cookieX !== '') {
            $nc('.middle_left').css('width', (cookieX - 1) + 'px');
            $nc('.middle_right').css('margin-left', cookieX + 'px');
        }
    }

    $nc('.middle_border').bind('mousedown', scrollerMouseDown);
    $nc('.middle_border').bind('touchstart', scrollerMouseDown);
    scrollerOnShow();

    //--------------------------------------------------------------------------


    var close_timeout, menu_isOn;
    var menu_button = $nc('.nc-navbar > ul > li');

    $nc('.nc-navbar > ul > li > a').click( function(e) {
        if (this.getAttribute('href') == '#' && !this.getAttribute('onclick'))
        {
            e.preventDefault();
        }
    });

    function menu_resize(el)
    {
        var menu = $nc('ul', el);

        if (0 == menu.length) {
            return;
        }

        menu.css({height:'auto'});

        var total_height = $nc(document.body).height();

        var bottom_offset = 20;
        if ( (menu.offset().top + menu.height() + bottom_offset*0.3) > total_height) {
            menu.height( total_height - menu.offset().top - bottom_offset );
        }
    }

    menu_button.click(function() {
        menu_isOn = $nc('>ul', this).is(':visible');
        menu_switch($nc(this));
        menu_resize($nc(this));
    });

    var menu_overlay = false;

    function menu_switch(menu) {
        if (menu.children('a').next().is('ul,div')) {
            var was_on = menu_isOn;
            menu_close();
            if (was_on) {
                return;
            }

            menu_isOn = true;

            menu.addClass('nc--clicked');
            var menu_ul = $nc('ul', menu);
            // menu_ul.show();
            // $nc('li', menu_ul).css({display:'block'});
            // menu_ul.css({backgroundColor:'#F00'});

            menu_isOn = true;

            // Слой поверх контента, для закрытия открытого меню при клике в контентную область
            if ( ! menu_overlay) {
                menu_overlay = $nc('<div class="main_menu_overlay"></div>').css({
                        position:          'absolute',
                        top:               $nc('body>.nc-navbar').height(),
                        left:              0,
                        width:             '100%',
                        height:            '100%',
                        // backgroundColor: '#FFF',
                        // opacity:         0.2,
                        zIndex:            1
                }).bind('click touchstart', function() {menu_close(); return false;});
                $nc('body').append(menu_overlay);
            }
            menu_overlay.show();
            setTimeout(function() { menu_isOn = true; }, 500);
        }
    }

    function menu_close() {
        $nc('.nc-navbar > ul li').removeClass('nc--clicked');
        $nc('.nc-navbar > ul li ul:visible').hide();
        $nc('.main_menu_overlay').hide();
        menu_isOn = false;
    }

    $nc('.nc-navbar > ul > li').bind('mouseleave', function() {
        close_timeout = setTimeout(menu_close,500);
    });

    $nc('.nc-navbar > ul > li').mouseenter(function() {
        clearTimeout(close_timeout);
    });

    if (!isTouch()) {
        menu_button.mouseenter(function() {
            if (menu_isOn) {
                menu_isOn = false;
                menu_switch($nc(this));
                menu_resize($nc(this));
            }
        });
    }

    /* �������� ������� ������ ���� */
    var menuSlider1StepDefault = 100,
    menuSlider1Step        = 0,
    menuSlider1Speed       = 250,
    menuSlider1MinPos      = 30,
    menuSlider1MaxPos      = 0,
    menuSlider1CurrentLeft = 0;

    function generateSlider1()
    {
        $nc('.slider_block_1 .left_arrow').unbind('click');
        $nc('.slider_block_1 .right_arrow').unbind('click');
        if (isTouch()) $nc('.slider_block_1 .slide').unbind('touchstart');

        var widthSlide = 0;

        $nc('.slider_block_1 ul li').each(function() {
            if (!$nc(this).hasClass('clear'))
            {
                widthSlide += $nc(this).outerWidth(true);
            }
        });

        if (widthSlide <= $nc('.slider_block_1 .overflow').width())
        {
            $nc('.slider_block_1 .slide').css('width', '100%');
            $nc('.slider_block_1 .left_gradient, .slider_block_1 .right_gradient, .slider_block_1 .arrow').hide();
            $nc('.slider_block_1 .slide').css({'left': 0});
        }
        else
        {
            $nc('.slider_block_1 .slide').css('width', widthSlide + 'px');
            $nc('.slider_block_1 .left_gradient, .slider_block_1 .right_gradient, .slider_block_1 .arrow').show();
            if (isTouch()) $nc('.slider_block_1 .slide').bind('touchstart', menuSliderTouchDown1);
            $nc('.slider_block_1 .slide').css({'left': 30});
        }

        menuSlider1MaxPos = (widthSlide - $nc('.slider_block_1 .overflow').width() + 30) * -1;
        $nc('.slider_block_1 .left_arrow').bind('click', menuSliderLeft1);
        $nc('.slider_block_1 .right_arrow').bind('click', menuSliderRight1);
    }

    menuSliderLeft1 = function()
    {
        $nc('.slider_block_1 .left_arrow').unbind('click');
        $nc('.slider_block_1 .right_arrow').unbind('click');

        menuSlider1CurrentLeft = parseInt($nc('.slider_block_1 .slide').css('left'));
        if ((menuSlider1CurrentLeft + menuSlider1StepDefault) >= menuSlider1MinPos)
            menuSlider1Step = menuSlider1MinPos - menuSlider1CurrentLeft;
        else
            menuSlider1Step = menuSlider1StepDefault;

        $nc('.slider_block_1 .slide').animate({
            'left' : '+=' + menuSlider1Step + 'px'
        }, menuSlider1Speed, function() {
            menuSlider1CurrentLeft = parseInt($nc('.slider_block_1 .slide').css('left'));
            if (menuSlider1CurrentLeft != menuSlider1MinPos)
            {
                $nc('.slider_block_1 .left_arrow').bind('click', menuSliderLeft1);
                $nc('.slider_block_1 .right_arrow').bind('click', menuSliderRight1);
            }
            else if (menuSlider1CurrentLeft == menuSlider1MinPos)
            {
                $nc('.slider_block_1 .right_arrow').bind('click', menuSliderRight1);
            }
        });
    }

    menuSliderRight1 = function()
    {
        $nc('.slider_block_1 .left_arrow').unbind('click');
        $nc('.slider_block_1 .right_arrow').unbind('click');

        menuSlider1CurrentLeft = parseInt($nc('.slider_block_1 .slide').css('left'));
        if ((menuSlider1CurrentLeft - menuSlider1StepDefault) <= menuSlider1MaxPos)
            menuSlider1Step = (menuSlider1MaxPos - menuSlider1CurrentLeft) * -1;
        else
            menuSlider1Step = menuSlider1StepDefault;

        $nc('.slider_block_1 .slide').animate({
            'left' : '-=' + menuSlider1Step + 'px'
        }, menuSlider1Speed, function() {
            menuSlider1CurrentLeft = parseInt($nc('.slider_block_1 .slide').css('left'));
            if (menuSlider1CurrentLeft != menuSlider1MaxPos)
            {
                $nc('.slider_block_1 .left_arrow').bind('click', menuSliderLeft1);
                $nc('.slider_block_1 .right_arrow').bind('click', menuSliderRight1);
            }
            else if (menuSlider1CurrentLeft == menuSlider1MaxPos)
            {
                $nc('.slider_block_1 .left_arrow').bind('click', menuSliderLeft1);
            }
        });
    }



    /* �������� �������� ������ ���� */
    var menuSlider2StepDefault = 100,
    menuSlider2Step = 0,
    menuSlider2Speed = 250,
    menuSlider2MinPos = 30,
    menuSlider2MaxPos = 0,
    menuSlider2CurrentLeft = 0;

    function generateSlider2()
    {
        $nc('.slider_block_2 .left_arrow').unbind('click');
        $nc('.slider_block_2 .right_arrow').unbind('click');
        if (isTouch()) $nc('.slider_block_2 .slide').unbind('touchstart');

        var widthSlide = 10;

        $nc('.slider_block_2 ul li').each(function() {
            if (!$nc(this).hasClass('clear'))
            {
                widthSlide += $nc(this).outerWidth(true);
            }
        });

        // Прячем кнопки слайдера
        if (widthSlide <= $nc('.slider_block_2 .overflow').width())
        {
            $nc('.slider_block_2 .slide').css('width', '100%');
            $nc('.slider_block_2 .left_gradient, .slider_block_2 .right_gradient, .slider_block_2 .arrow').hide();
            $nc('.slider_block_2 .slide').css('left', 0);
        }
        // Показываем кнопки слайдера
        else
        {
            $nc('.slider_block_2 .slide').css('width', widthSlide + 'px');
            $nc('.slider_block_2 .left_gradient, .slider_block_2 .right_gradient, .slider_block_2 .arrow').show();
            if (isTouch()) $nc('.slider_block_2 .slide').bind('touchstart', menuSliderTouchDown2);
            $nc('.slider_block_2 .slide').css({'left': 30});
        }
        menuSlider2MaxPos = (widthSlide - $nc('.slider_block_2 .overflow').width() + 30) * -1;
        $nc('.slider_block_2 .left_arrow').bind('click', menuSliderLeft2);
        $nc('.slider_block_2 .right_arrow').bind('click', menuSliderRight2);
    }

    menuSliderLeft2 = function()
    {
        $nc('.slider_block_2 .left_arrow').unbind('click');
        $nc('.slider_block_2 .right_arrow').unbind('click');

        menuSlider2CurrentLeft = parseInt($nc('.slider_block_2 .slide').css('left'));
        if ((menuSlider2CurrentLeft + menuSlider2StepDefault) >= menuSlider2MinPos)
            menuSlider2Step = menuSlider2MinPos - menuSlider2CurrentLeft;
        else
            menuSlider2Step = menuSlider2StepDefault;

        $nc('.slider_block_2 .slide').animate({
            'left' : '+=' + menuSlider2Step + 'px'
        }, menuSlider2Speed, function() {
            menuSlider2CurrentLeft = parseInt($nc('.slider_block_2 .slide').css('left'));
            if (menuSlider2CurrentLeft != menuSlider2MinPos)
            {
                $nc('.slider_block_2 .left_arrow').bind('click', menuSliderLeft2);
                $nc('.slider_block_2 .right_arrow').bind('click', menuSliderRight2);
            }
            else if (menuSlider2CurrentLeft == menuSlider2MinPos)
            {
                $nc('.slider_block_2 .right_arrow').bind('click', menuSliderRight2);
            }
        });
    }

    menuSliderRight2 = function()
    {
        $nc('.slider_block_2 .left_arrow').unbind('click');
        $nc('.slider_block_2 .right_arrow').unbind('click');

        menuSlider2CurrentLeft = parseInt($nc('.slider_block_2 .slide').css('left'));
        if ((menuSlider2CurrentLeft - menuSlider2StepDefault) <= menuSlider2MaxPos)
            menuSlider2Step = (menuSlider2MaxPos - menuSlider2CurrentLeft) * -1;
        else
            menuSlider2Step = menuSlider2StepDefault;

        $nc('.slider_block_2 .slide').animate({
            'left' : '-=' + menuSlider2Step + 'px'
        }, menuSlider2Speed, function() {
            menuSlider2CurrentLeft = parseInt($nc('.slider_block_2 .slide').css('left'));
            if (menuSlider2CurrentLeft != menuSlider2MaxPos)
            {
                $nc('.slider_block_2 .left_arrow').bind('click', menuSliderLeft2);
                $nc('.slider_block_2 .right_arrow').bind('click', menuSliderRight2);
            }
            else if (menuSlider2CurrentLeft == menuSlider2MaxPos)
            {
                $nc('.slider_block_2 .left_arrow').bind('click', menuSliderLeft2);
            }
        });
    }


    /* �������� ������� ��� ios � android */
    if (isTouch())
    {
        //������ ���������
        $nc('.nc-navbar .nc-tray').css('margin-right', '5px');

        var startTouch1 = 0,
        pageStartTouch1 = 0,
        newLeft1 = 0,
        touch1,
        currentTouch1;

        menuSliderTouchDown1 = function(e) {
            if (e.target && e.target.nodeName == 'SPAN') {
                return;
            }
            $nc(document).unbind('touchmove');
            $nc(document).unbind('touchend');
            $nc('.slider_block_1 .slide').unbind('touchstart');
            $nc('.slider_block_1 .left_arrow').unbind('click');
            $nc('.slider_block_1 .right_arrow').unbind('click');

            e.preventDefault();
            touch1 = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
            pageStartTouch1 = touch1.pageX;
            startTouch1 = parseInt($nc('.slider_block_1 .slide').css('left'));

            $nc(document).bind('touchmove', menuSliderTouchMove1);
            $nc(document).bind('touchend', menuSliderTouchUp1);
        }

        menuSliderTouchMove1 = function(e) {
            e.preventDefault();
            touch1 = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
            newLeft1 = touch1.pageX - pageStartTouch1 + startTouch1;

            currentTouch1 = parseInt($nc('.slider_block_1 .slide').css('left'));

            $nc('.slider_block_1 .slide').css('left', newLeft1 + 'px');
            return false;
        }

        menuSliderTouchUp1 = function(e) {
            $nc(document).unbind('touchmove');
            $nc(document).unbind('touchend');
            $nc('.slider_block_1 .slide').bind('touchstart', menuSliderTouchDown1);

            if (currentTouch1 < menuSlider1MinPos && currentTouch1 > menuSlider1MaxPos)
            {
                $nc('.slider_block_1 .left_arrow').bind('click', menuSliderLeft1);
                $nc('.slider_block_1 .right_arrow').bind('click', menuSliderRight1);
            }
            else if (currentTouch1 >= menuSlider1MinPos)
            {
                $nc('.slider_block_1 .slide').animate({
                    'left' : menuSlider1MinPos + 'px'
                }, 200);
                $nc('.slider_block_1 .right_arrow').bind('click', menuSliderRight1);
            }
            else if (currentTouch1 <= menuSlider1MaxPos)
            {
                $nc('.slider_block_1 .slide').animate({
                    'left' : menuSlider1MaxPos + 'px'
                }, 200);
                $nc('.slider_block_1 .left_arrow').bind('click', menuSliderLeft1);
            }
        }


        //������ ���������
        var startTouch2 = 0,
        pageStartTouch2 = 0,
        newLeft2 = 0,
        touch2,
        currentTouch2;

        menuSliderTouchDown2 = function(e) {
            $nc(document).unbind('touchmove');
            $nc(document).unbind('touchend');
            $nc('.slider_block_2 .slide').unbind('touchstart');
            $nc('.slider_block_2 .left_arrow').unbind('click');
            $nc('.slider_block_2 .right_arrow').unbind('click');

            e.preventDefault();
            touch2 = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
            pageStartTouch2 = touch2.pageX;
            startTouch2 = parseInt($nc('.slider_block_2 .slide').css('left'));

            $nc(document).bind('touchmove', menuSliderTouchMove2);
            $nc(document).bind('touchend', menuSliderTouchUp2);
        }

        menuSliderTouchMove2 = function(e) {
            e.preventDefault();
            touch2 = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
            newLeft2 = touch2.pageX - pageStartTouch2 + startTouch2;

            currentTouch2 = parseInt($nc('.slider_block_2 .slide').css('left'));

            $nc('.slider_block_2 .slide').css('left', newLeft2 + 'px');
            return false;
        }

        menuSliderTouchUp2 = function(e) {
            $nc(document).unbind('touchmove');
            $nc(document).unbind('touchend');
            $nc('.slider_block_2 .slide').bind('touchstart', menuSliderTouchDown2);

            if (currentTouch2 < menuSlider2MinPos && currentTouch2 > menuSlider2MaxPos)
            {
                $nc('.slider_block_2 .left_arrow').bind('click', menuSliderLeft2);
                $nc('.slider_block_2 .right_arrow').bind('click', menuSliderRight2);
            }
            else if (currentTouch2 >= menuSlider2MinPos)
            {
                $nc('.slider_block_2 .slide').animate({
                    'left' : menuSlider2MinPos + 'px'
                }, 200);
                $nc('.slider_block_2 .right_arrow').bind('click', menuSliderRight2);
            }
            else if (currentTouch2 <= menuSlider2MaxPos)
            {
                $nc('.slider_block_2 .slide').animate({
                    'left' : menuSlider2MaxPos + 'px'
                }, 200);
                $nc('.slider_block_2 .left_arrow').bind('click', menuSliderLeft2);
            }
        }
    }

    //generateSlider1();
    //generateSlider2();

});
