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

// slider, top menu, tree selector

// ===========================================================================
// slider functions
// ===========================================================================
bindEvent(window, 'load', function() {

    var slideBar = document.getElementById('slideBar');
    var leftPane = document.getElementById('leftPane');
    var topLeftPane = document.getElementById('topLeftPane');
    var mainContainer = document.getElementById('mainContainer');
    var topMainContainer = document.getElementById('topMainContainer');
    var minPaneWidth = 200;
    var sliderEventIds = [];

    //FIXME: Файл можно выкинуть т.к. дальше этого условия он отрабатывается!!!
    //Скрипты слайдера тут: admin/js/main.js
    if ( !slideBar || !leftPane || !topLeftPane || !mainContainer || !topMainContainer) {
        return;
    }

    var divOverIFrame = document.createElement('DIV');
    divOverIFrame.style.position = 'absolute';
    divOverIFrame.style.top = '0';
    divOverIFrame.style.bottom = '0';
    divOverIFrame.style.left = '0';
    divOverIFrame.style.right = '0';

    function handleSliderMousedown(e) {
        if (!e) e = window.event;
        slideBar.style.backgroundColor = 'silver';
        slideBar.style.position = 'absolute';
        slideBar.style.top = 'auto';
        slideBar.mousedownOffsetX = e.clientX - slideBar.parentNode.offsetLeft;

        document.body.appendChild(divOverIFrame);

        sliderEventIds.push(bindEvent(document.body, 'mousemove', handleSliderMousemove));
        sliderEventIds.push(bindEvent(document.body, 'mouseup', handleSliderMouseup));

        if (document.attachEvent) {
            document.body.setCapture();
        } // IE

        if(e.preventDefault) {
            e.preventDefault();
        } else {
            e.returnValue = false; // IE branch - do more tests if that's not enough
        }
        return false;
    }

    function handleSliderMousemove(e) {
        if (!e) e = window.event;

        var x = e.clientX;
        var maxPaneWidth = screen.availWidth - minPaneWidth * 2;

        if (e.clientX < minPaneWidth) x = minPaneWidth;
        if (e.clientX > maxPaneWidth) x = maxPaneWidth;

        slideBar.style.left = x + 'px';
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        e.cancelBubble = true;
    }

    function handlerSliderMouseDblClick(e) {
        if (!e) e = window.event;

        var topSelectorBar = document.getElementById('treeSelector');

        if (topSelectorBar.clientWidth) topSelectorBar.style.width = topSelectorBar.clientWidth;
        var x = topSelectorBar.style.width.replace(/px/, '');

        if (!topSelectorBar.clientWidth) {
            topSelectorBar.style.display = 'block';
        }
        else {
            topSelectorBar.style.display = 'none';
            x = 0;
        }

        topLeftPane.style.width = x + 'px';
        leftPane.style.width = x + 'px';

        topMainContainer.style.width = document.body.clientWidth - x - slideBar.clientWidth + 'px';
        mainContainer.style.width = document.body.clientWidth - x - slideBar.clientWidth + 'px';

        //var cwindow = document.getElementById("mainViewIframe").contentWindow;
        //cwindow.resize_all_editareas();
    }

    function handleSliderMouseup(e) {
        // console.log('tada')
        if (!e) e = window.event;
        document.body.removeChild(divOverIFrame);
        if ( leftPane ) {
            // variables
            var x = e.clientX - slideBar.mousedownOffsetX;
            var maxPaneWidth = screen.availWidth - minPaneWidth * 2;
            var topSelectorBar = document.getElementById('treeSelector');
            // min - max width
            if (e.clientX < minPaneWidth) {
                x = x ? minPaneWidth : topSelectorBar.style.width.replace(/px/, '');
            }
            if (e.clientX > maxPaneWidth) x = maxPaneWidth;
            // left block
            topLeftPane.style.width = x + 'px';
            leftPane.style.width = x + 'px';
            // main block
            topMainContainer.style.width = document.body.clientWidth - x - slideBar.clientWidth + 'px';
            mainContainer.style.width = document.body.clientWidth - x - slideBar.clientWidth +'px';
        }
        slideBar.style.backgroundColor = 'transparent';
        slideBar.style.left = 'auto';

        // reset fixed width and display, may exist if slider been hidden or shoween
        topSelectorBar.style.width = '';
        topSelectorBar.style.display = 'block';

        var prevClassName = document.body.className;
        document.body.className = prevClassName;

        if (e.stopPropagation) {
            e.stopPropagation();
        }

        e.cancelBubble = true;
        for (var i=0; i < sliderEventIds.length; i++) {
            unbindEvent(sliderEventIds[i]);
        }

        sliderEventIds = [];
        if (document.detachEvent) {
            document.body.releaseCapture();
        } // IE

        var cwindow = document.getElementById("mainViewIframe").contentWindow;
        cwindow.resize_all_editareas();
    }


    if ( document.addEventListener ) {
        slideBar.addEventListener('mousedown', handleSliderMousedown, false);
        slideBar.addEventListener('dblclick', handlerSliderMouseDblClick, false);
    }

    else if ( document.attachEvent ) {
        slideBar.attachEvent('onmousedown', handleSliderMousedown);
        slideBar.attachEvent('ondblclick', handlerSliderMouseDblClick);
    }

} );


// ===========================================================================
// top menu functions
// ===========================================================================

// INIT
bindEvent(window, 'load',  function() {
    var mainMenu = document.getElementById('mainMenu');
    if (mainMenu) {
        menuInit(mainMenu);
    }
} );

var menuHideTimer;
var menuOpened = false;

function menuInit(oNode) {
    var oNodeItems = oNode.childNodes;
    if (!oNodeItems) return;
    for (var i = 0; i < oNodeItems.length; i++) {
        if (oNodeItems[i].nodeType == 1) {
            if (oNodeItems[i].tagName == 'LI') {
                if (oNodeItems[i].parentNode.id=='mainMenu') { // First Level
                    oNodeItems[i].levelOne = true;
                    bindEvent(oNodeItems[i], 'click', menuClickTopLevel);
                }

                bindEvent(oNodeItems[i], 'mouseover', menuNodeOver);
                bindEvent(oNodeItems[i], 'mouseout', menuNodeOut);
            }
            else if (oNodeItems[i].tagName == 'UL') {
                if (oNode.tagName == 'LI' && oNode.parentNode.id != 'mainMenu') {
                    oNode.firstChild.style.background = "url("+ICON_PATH+"arr_right.gif) center right no-repeat";
                }
            }
            else if (oNodeItems[i].tagName=='A') {
                var href = oNodeItems[i].href;
                // если ссылка не пустая, при нажатии на этот пункт закрыть меню
                if (href && href.substring(href.length-1) != '#') {
                    bindEvent(oNodeItems[i], 'click', menuHideOnItemClick);
                }
            }
            menuInit(oNodeItems[i]);
        }
    } // of for
} // of menuInit

var menuOpenedNodes = [];

function menuClickTopLevel(e) {
    // remove focus from link
    try {
        this.firstChild.blur();
    } catch(exptn) {}
    // open/close
    menuOpened ? menuHide() : menuShow(this);
    if (e && e.preventDefault) {
        e.preventDefault();
    }
    else if (window.event) {
        window.event.returnValue = false;
    }
}

function menuHideOnItemClick(e) {
    this.blur();
    menuHide();
    if (e && e.stopPropagation) {
        e.stopPropagation();
    }
    else if (window.event) {
        window.event.cancelBubble = true;
    }
}

function menuNodeOver(e) {
    clearInterval(menuHideTimer);
    if (this.levelOne && !menuOpened) {
        this.className = 'top_level_hover';
        return;
    }
    menuHide();
    menuShow(this);
    if (window.event) {
        window.event.cancelBubble = true;
    }
    else {
        e.stopPropagation();
    }
}

function menuNodeOut(e) {
    if (this.levelOne && !menuOpened) {
        this.className = '';
        return;
    }
    menuHideTimer = setTimeout(menuHide, 1000);
    if (window.event) {
        window.event.cancelBubble = true;
    }
    else {
        e.stopPropagation();
    }
}

function menuShow(oNode) {
    if (typeof oNode != 'object') return;
    if (dragManager && dragManager.dragInProgress) return;
    oNode.className = oNode.className ? oNode.className + ' on' : 'on';
    var oParentNode = oNode.parentNode;
    if (oParentNode.id != 'mainMenu') menuShow(oParentNode);
    menuOpenedNodes.push(oNode);
    menuOpened = true;
}

function menuHide() {
    for (var i=0; i < menuOpenedNodes.length; i++) {
        if (typeof menuOpenedNodes[i] != 'object') continue;
        menuOpenedNodes[i].className = menuOpenedNodes[i].className.replace(/top_level_hover/,'').replace(/on/,'');
    }
    menuOpenedNodes = [];
    menuOpened = false;
}

// ==========================================================================
// tree mode selector functions
// ==========================================================================
treeSelector = {

    currentMode: null,

    // initialize
    init: function() {

        var selector = document.getElementById('treeSelector');
        if (!selector) return false;

        if (document.getElementById('treeSelectorItems').getElementsByTagName('LI').length < 2) {
            // пользователю не из чего выбирать
            // убрать стрелочку
            document.getElementById('treeSelectorCaption').style.background = 'transparent';
            return true;
        }

        bindEvent(selector, 'click', treeSelector.toggle);

        // bind event handlers to selector items
        var selectorItems = document.getElementById('treeSelectorItems').childNodes;
        for (var i = 0; i < selectorItems.length; i++) {
            if (selectorItems[i].tagName == 'LI') {
                bindEvent(selectorItems[i], 'click', treeSelector.selectItem);
                bindEvent(selectorItems[i], 'mouseover', function() {
                    this.className = 'over';
                });
                bindEvent(selectorItems[i], 'mouseout',  function() {
                    this.className = '';
                });
            }
        }

    }, // of "init()"

    // show or hide
    toggle: function() {
        var oSelector = document.getElementById('treeSelector')
        oSelector.className = oSelector.className ? '' : 'on';
    }, // of "toggle()"

    //select item on click
    selectItem: function(e) {
        if (!e && window.event) {
            var oItem = window.event.srcElement;
        }
        else if (e && e.target) {
            var oItem = e.target;
        }

        var modeName = oItem.id.replace(/^treemode_/,"");
        treeSelector.changeMode(modeName);
    },

    // reload tree iframe
    changeMode: function(modeName, selectedNode) {

        var tree = document.getElementById('treeIframe');

        if (tree) {
            if (treeSelector.currentMode == modeName) {
                if (selectedNode) {
                    tree.contentWindow.tree.selectNode(selectedNode);
                    tree.contentWindow.tree.toggleNode(selectedNode, false, true);
                }
                return;
            }

            tree.contentWindow.location =
            ADMIN_PATH + 'tree_frame.php?mode='+modeName +
            (selectedNode ? '&selected_node='+selectedNode : '');
            treeSelector.currentMode = modeName;

            $nc('#tree_mode_name').html(tree_modes[modeName]);
            $nc(tree).attr('title', tree_modes[modeName]);

            nc.process_start('treeSelector.changeMode()');
            $nc(tree).load(function(){
                nc.process_stop('treeSelector.changeMode()');
            });

        }
    },

    removeTreeHighlight: function() {
        var tree = document.getElementById('treeIframe');

        if (tree && tree.contentWindow.tree) {
            tree.contentWindow.tree.removeHighlight();
        }
    }

}; // of treeSelector

bindEvent(window, 'load', treeSelector.init);


// ============================================================================
// LOGIN FORM
// ============================================================================

var loginFormOnSuccess = null;
/**
 *
 * @param {String} onSuccess js code evaluated on successfull authorization
 */
function loginFormShow(onSuccess) {
    document.getElementById('loginDialog').style.display = '';
    loginFormOnSuccess = onSuccess;
}

function loginFormPost() {
    var form = document.getElementById('loginForm');
    var req = new httpRequest();
    var status = req.request('POST', ADMIN_PATH + 'index.php',
    {
        'AUTH_USER': form.login.value,
        'AUTH_PW': form.password.value,
        'AuthPhase': 1,
        'NC_HTTP_REQUEST': 1
    },

    {
        '200': 'if (loginFormOnSuccess) { eval(loginFormOnSuccess); loginFormHide(); } '
    }
    );
}

function loginFormHide() {
    document.getElementById('loginDialog').style.display = 'none';
}

// ============================================================================
// Toolbars
// ============================================================================
toolbar = function(toolbarId, buttons) {

    this.groups = {};
    this.buttons = [];
    this.toolbarId = toolbarId;

    if (buttons) {
    // create buttons
    }
    else {
        this.makeEmptyToolbar();
    }

    toolbar.toolbarList[toolbarId] = this;
}

toolbar.toolbarList = {};

// toolbar class methods
toolbar.prototype.clear = function() {
    this.groups = [];
    document.getElementById(this.toolbarId).innerHTML = '';
}

toolbar.prototype.makeEmptyToolbar = function() {
    this.groups = [];
    // this need for clear block content in opera
    document.getElementById(this.toolbarId).innerHTML = "<img src='"+ICON_PATH+"px.gif' height='1' width='1' border='0' alt=''>";
}

/**
  * Добавить кнопку на тулбар
  * @param {Object} параметры кнопки
  *   id
  *   image
  *   caption
  *   title ----- (пока не используется)
  *   action
  *   className
  *   group - Кнопки из одной группы действуют вместе - активной
  *           может быть только одна кнопка из группы.
  *           Если группа не задана, действует как простая кнопка (выполняет
  *           действие, но не переходит в состояние "нажата")
  *   dragEnabled
  *   acceptDropFn
  *   onDropFn
  *   metadata: key->value (свойство key со значением value будет присвоено кнопке)
  *
  */
toolbar.prototype.addButton = function(btnParams) {

    var btn = document.createElement('div');

    if (btnParams.metadata) {
        for (var i in btnParams.metadata) {
            btn[i] = btnParams.metadata[i];
        }
    }

    btn.id = this.toolbarId + '_' + btnParams.id;
    btn.toolbarId = this.toolbarId;
    btn.className = 'button' + (btnParams.className ? ' ' + btnParams.className : '');


    //  btn.href = "#";
    btn.state = 'off'; // current state, on or off
    btn.action = btnParams.action;

    if (btnParams.dragEnabled) {
        top.dragManager.addDraggable(btn);
    }

    if (btnParams.acceptDropFn && btnParams.onDropFn) {
        var arrow = {
            name: 'arrowRight',
            bottom: -9,
            left: 4
        };
        top.dragManager.addDroppable(btn, eval(btnParams.acceptDropFn), eval(btnParams.onDropFn), arrow);
    }

    if (btnParams.group) {
        btn.groupId = btnParams.group;
        if (!this.groups[btnParams.group]) {
            this.groups[btnParams.group] = [];
        }
        this.groups[btnParams.group].push(btn.id);
    }

    btn.onselectstart = top.dragManager.cancelEvent;

    btn.turnOff = function() {
        this.className = this.className.replace(/\s*button_on\s*/, '');
        this.state = 'off';
        this.innerHTML = btnParams.caption ? "<li class='" + this.className + "'>" + btnParams.caption + "</li>" : "";
    }

    btn.turnOn = function() {
        if (this.groupId) {
            if (this.state == 'on') {
                return false;
            }
            var tb = toolbar.toolbarList[this.toolbarId];
            for (var i in tb.groups[this.groupId]) {
                var btnId = tb.groups[this.groupId][i];
                if (btnId!=this.id) {
                    var btn = document.getElementById(btnId);
                    if (btn) btn.turnOff();
                }
            }
        }
        this.className += " button_on";
        this.state = 'on';
        this.innerHTML = btnParams.caption ? "<li class='" + this.className + "'>" + btnParams.caption + "</li>" : "";
    }

    btn.onclick =  function() {
        if (this.groupId) {
            this.turnOn();
        }
        eval(this.action);
        return false;
    }

    document.getElementById(this.toolbarId).appendChild(btn);
    this.buttons.push(btn.id);
}

toolbar.prototype.getButton = function(btnId) {
    return document.getElementById(this.toolbarId + '_' + btnId);
}

toolbar.prototype.buttonIsActive = function(btnId) {
    var btn = document.getElementById(this.toolbarId + '_' + btnId);
    if (!btn) return false;
    return (btn.state=='on');
}

toolbar.prototype.removeButton = function(btnId) {
    var btn = this.getButton(btnId), classNames = [];
    if (!btn) return;
    // move divider classes to the next button   /* to be refined if needed */
    if ((classNames = btn.className.match(/(divider\w+)/g)) && btn.nextSibling) {
        for (var i=0; i < classNames.length; i++) {
            btn.nextSibling.className += ' ' + classNames[i];
        }
    }

    if (btn.groupId) {
        for (var i=0; i < this.groups.length; i++) {
            if (this.groups[btn.groupId][i] == btn.id) {
                this.groups[btn.groupId].splice(i, 0);
            }
        }

    }
    btn.parentNode.removeChild(btn);
}


// ============================================================================
// Resize in mozilla and opera
// ============================================================================

function triggerResize() {
    setTimeout(resizeApp, 500);
}

function resizeApp() {
    var h = window.innerHeight, w = window.innerWidth;
    if (h && w) { // mozilla

        var oMainView = document.getElementById("mainView");
        if (!oMainView) return;

        // determine header height
        var headerRows = ['trDummyTop', 'trHeader', 'trMainMenu'];
        var headerHeight = 0;
        for (var i in headerRows) {
            var headerRow = document.getElementById(headerRows[i]);
            if (headerRow) {
                headerHeight += headerRow.offsetHeight;
            }
        }

        oMainView.style.height = (h - headerHeight) + 'px';
        document.body.style.width = w + 'px';
    }
    else { // IE

    }

}

/**
  * DROP HANDLERS
  */
function subclassAcceptDrop(e) {
    var dragged = top.dragManager.draggedInstance,
    target  = top.dragManager.droppedInstance;

    // перемещение объекта в другой шаблон-в-разделе того же типа
    // нельзя 'переместить' объект в шаблон-в-разделе, в котором он уже находится
    if (dragged.type=='message' && dragged.typeNum==target.typeNum &&
        top.dragManager.draggedObject.getAttribute('messageSubclass')!=target.id)
        {
        return true;
    }

    return false;
}

function subclassOnDrop(e) {
    var dragged = top.dragManager.draggedInstance,
    target  = top.dragManager.droppedInstance;

    // move message to another subclass
    if (dragged.type == 'message') {
        moveMessage(dragged.typeNum, dragged.id, target.id);
    }

}


/**
  * переместить объект в другой раздел.
  * используется в d&d: message-to-subclass (container.js),
  * message-to-subdivision (tree_frame.js)
  */
function moveMessage(classId, messageId, destinationSubdivisionId) {

    var xhr = new httpRequest(),
    res = xhr.getJson(top.ADMIN_PATH + 'subdivision/drag_manager_message.php',
    {
        'dragged_type': 'message',
        'dragged_class': classId,
        'dragged_id': messageId,
        'target_type': 'subclass',
        'target_id': destinationSubdivisionId
    } );

    if (res==1) {
        // reload iframe
        top.mainView.refreshIframe();
    }
}

/**
  * Если объект был перетащен на раздел, в котором более одного подходящего
  * шаблона-в-разделе, показать варианты
  */
function showMessageToSubdivisionDialog(messageClass, messageId, subdivisionId) {
    document.getElementById('messageToSubdivisionIframe').contentWindow.location.href =
    top.ADMIN_PATH + 'subdivision/subclass_list.php?class_id='+messageClass+'&message_id='+messageId+'&sub_id='+subdivisionId;
    document.getElementById('messageToSubdivisionDialog').style.display = '';
}


// END OF DRAG HANDLERS
function updateUpdateIndicator(active) {
    firstChild = document.getElementById('mainMenuUpdate').firstChild.firstChild;
    if ( firstChild )
        firstChild.src = top.ICON_PATH + 'i_update' + (active ? '_active' : '') + '.gif';
}

function updateSysMsgIndicator(active) {
    var trayMessagesIcon = document.getElementById('trayMessagesIcon');
    if ( trayMessagesIcon ) {
        trayMessagesIcon.className = active ? '' : 'nc--disabled';
    }
}



// Init'n

bindEvent(window, 'resize', triggerResize);

if (window.opera) {
    bindEvent(window, 'load', function() {
        resizeApp();
    });
}
// url dispatching
urlDispatcher = {
    ajaxAdd: false, // dunno why they wanted that
    intervalId: 0,
    userAreaRegExp: /(\w+(?:\.\w+)*)(?:\((.+?)\))?/, // cache compiled regexps (in sake of perfomance)

    // initialize
    init: function() {
        if (!this.intervalId) {
            urlDispatcher.intervalId = setInterval('urlDispatcher.observe()', 150);
        }
    },

    // слежение за fragment-частью URL
    observe: function() {
        if ((window.location.oldHash != window.location.hash) || this.ajaxAdd) {
            this.ajaxAdd = false;
            window.location.oldHash = window.location.hash;
            this.process(window.location.hash);
        }
    },

    // change hash (call
    load: function(hash, newWindow) {
        this.ajaxAdd = nc_is_frame();
        if (!newWindow) {
            window.location.hash = hash;
        } else {
            window.open(hash, newWindow);
        }
    },

    // update without processing:
    updateHash: function(hash) {
        if (hash.substr(0, 1) != '#') {
            hash = '#' + hash;
        }

        if (decodeURIComponent(window.location.oldHash) != decodeURIComponent(hash)) {
            window.location.oldHash = window.location.hash = hash;
        }
    },

    // просто смена url в главном фрейме
    route: {},

    // набор функций, реагирующих на определенный префикс в fragment
    // ключ — префикс, значение — функция
    prefixMatchers: {},

    // onHashChange
    process: function(hash) {
        hash = decodeURIComponent(hash.substring(1)); // remove '#'

        if (!hash) { // FIRST 'WELCOME' SCREEN
            //treeSelector.changeMode('sitemap');
            //FIRST_TREE_MODE пришел из index.php в началe, из переменной $treeMode; a она определена в function.inc.php
            treeSelector.changeMode(FIRST_TREE_MODE);

            //mainView.showStartScreen();
            //return;
            hash = '#index';
        }

        if (hash.match(this.userAreaRegExp)) {
            var functionName = RegExp.$1, param = RegExp.$2;

            // process url here
            // обработчики по префиксу
            for (var prefix in this.prefixMatchers) {
                if (functionName.indexOf(prefix) === 0) {
                    this.prefixMatchers[prefix](functionName, param);
                    return;
                }
            }

            // перезагрузка главного фрейма
            if (this.route[functionName]) {
                param = param.split(/\s*,\s*/);
                var url = this.route[functionName];

                for (var i=0; i < param.length; i++) {
                    url = url.replace(new RegExp("[$%]"+(i+1),"g"), param[i]);
                }

                // незамененные макропараметры нужно заменить нулем
                url = url.replace(new RegExp("[$%]([0-9])+","g"), 0);
                url = url.replace(/\$\d+/g, '');
                mainView.loadIframe(url);
                return;
            }
        }

        alert('Wrong params\n' + hash);
    }, // of urlDispatcher.process

    addRoutes: function(hashArray) {
        for (var newUrl in hashArray) {
            this.route[newUrl] = hashArray[newUrl];
        }

        return this;
    },

    addPrefixRouter: function(prefix, fn) {
        this.prefixMatchers[prefix] = fn;

        return this;
    }
};

bindEvent(window, 'load', function() {
    urlDispatcher.init();
});

urlDispatcher.route = {
    "blank": "about:blank",
    "index": ADMIN_PATH + "index_page.php",

    "catalogue.edit": ADMIN_PATH + "catalogue/index.php?action=edit&phase=2&CatalogueID=%1",
    "catalogue.design": ADMIN_PATH + "catalogue/index.php?action=design&phase=2&CatalogueID=%1",
    "catalogue.seo": ADMIN_PATH + "catalogue/index.php?action=seo&phase=2&CatalogueID=%1",
    "catalogue.system": ADMIN_PATH + "catalogue/index.php?action=system&phase=2&CatalogueID=%1",
    "catalogue.fields": ADMIN_PATH + "catalogue/index.php?action=fields&phase=2&CatalogueID=%1",

    // Создание раздела (parent_sub_id, catalogue_id)
    "subdivision.add": ADMIN_PATH + "subdivision/index.php?phase=2&ParentSubID=%1&CatalogueID=%2",

    // Редактирование раздела
    "subdivision.edit": ADMIN_PATH + "subdivision/index.php?phase=5&SubdivisionID=%1&view=edit",
    "subdivision.design": ADMIN_PATH + "subdivision/index.php?phase=5&SubdivisionID=%1&view=design",
    "subdivision.seo": ADMIN_PATH + "subdivision/index.php?phase=5&SubdivisionID=%1&view=seo",
    "subdivision.system": ADMIN_PATH + "subdivision/index.php?phase=5&SubdivisionID=%1&view=system",
    "subdivision.fields": ADMIN_PATH + "subdivision/index.php?phase=5&SubdivisionID=%1&view=fields",

    // Удаление раздела
    "subdivision.delete": ADMIN_PATH + "subdivision/index.php?phase=7&Delete%1=%1",

    // Информация о разделе
    "subdivision.info": ADMIN_PATH + "subdivision/index.php?phase=4&SubdivisionID=%1",

    // Удаленные объекты
    "subdivision.trashed_objects": ADMIN_PATH + "subdivision/index.php?phase=8&SubdivisionID=%1",

    // История в разделе
    "subdivision.versions": NETCAT_PATH + "action.php?ctrl=admin.subdivision&action=show_versions&subdivision_id=%1",
    "subdivision.versions.infoblock": NETCAT_PATH + "action.php?ctrl=admin.subdivision&action=show_versions&subdivision_id=%1&entity=infoblock",
    "subdivision.versions.object": NETCAT_PATH + "action.php?ctrl=admin.subdivision&action=show_versions&subdivision_id=%1&entity=object",

    // Список подразделов
    "subdivision.sublist": ADMIN_PATH + "subdivision/index.php?phase=1&ParentSubID=%1",

    // Список шаблонов в разделе
    // Права пользователей на действия в разделе
    "subdivision.userlist": ADMIN_PATH + "subdivision/index.php?phase=15&SubdivisionID=%1",

    // Просмотр раздела
    "subdivision.view": ADMIN_PATH + "subdivision/index.php?phase=14&SubdivisionID=%1",

    // Используемые шаблоны
    "subdivision.subclass": ADMIN_PATH + "subdivision/SubClass.php?SubdivisionID=%1",

    // Список сквозных инфоблоков
    "site.area": NETCAT_PATH + "action.php?ctrl=admin.infoblock&action=show_area_infoblocks&site_id=%1",

    // Демо контент
    "site.demo_content": NETCAT_PATH + "action.php?ctrl=admin.demo_content.demo_content&action=show_list_demo_content&site_id=%1",

    // Список шаблонов в разделе
    "subclass.list": ADMIN_PATH + "subdivision/SubClass.php?SubdivisionID=%1",

    // Создание шаблона в разделе
    "subclass.add": ADMIN_PATH + "subdivision/SubClass.php?phase=1&SubdivisionID=%1",

    // Редактирование шаблона в разделе
    "subclass.edit": ADMIN_PATH + "subdivision/SubClass.php?phase=3&SubClassID=%1&SubdivisionID=%2",

    // Удаление всех объектов в шаблоне-в-разделе
    "subclass.purge": NETCAT_PATH + "message.php?cc=%1&delete=1&inside_admin=1",

    // Просмотр шаблона раздела
    "subclass.view": ADMIN_PATH + "subdivision/index.php?phase=14&SubClassID=%2",

    // Список объектов
    "object.list": NETCAT_PATH + "?inside_admin=1&cc=%1",

    // Список объектов в виде таблицы
    "object.switch_view": NETCAT_PATH + "action.php?ctrl=admin.component&action=switch_view&cc=%1",

    // Отображение объекта
    "object.view": NETCAT_PATH + "full.php?inside_admin=1&cc=%1&message=%2",

    // Редактирование объекта
    "object.edit": NETCAT_PATH + "message.php?inside_admin=1&classID=%1&message=%2",

    // Удаление объекта
    "object.delete": NETCAT_PATH + "message.php?inside_admin=1&cc=%1&message=%2&delete=1",

    // Создание объекта
    "object.add": NETCAT_PATH + "add.php?inside_admin=1&cc=%1",

    // Список всех сайтов
    "site.list": ADMIN_PATH + "catalogue/index.php",

    // Создание сайта
    "site.add": NETCAT_PATH + "action.php?ctrl=admin.site&action=show_add_form",

    // Настройки сайта
    "site.edit": ADMIN_PATH + "catalogue/index.php?phase=2&type=2&CatalogueID=%1",

    // Удаление сайта
    "site.delete": ADMIN_PATH + "catalogue/index.php?phase=4&Delete%1=%1",

    // Карта сайта
    "site.map": ADMIN_PATH + "subdivision/full.php?CatalogueID=%1",

    // Статистика
    "site.stat.nc_stat": NETCAT_PATH + "modules/stats/admin.php?phase=9&cat_id=%1",
    "site.stat.openstat": NETCAT_PATH + "modules/stats/openstat/admin.php?catalog_page=%1",

    // Информация
    "site.info": ADMIN_PATH + "catalogue/index.php?phase=6&CatalogueID=%1",

    // Список разделов
    "site.sublist": ADMIN_PATH + "subdivision/index.php?phase=1&CatalogueID=%1&ParentSubID=%2",

    // Мастер создания сайта
    "site.wizard": ADMIN_PATH + "wizard/wizard_site.php?phase=%1&CatalogueID=%2",


    // ------------------------Работа с группами шаблонов---------------------
    // Добавление группы шаблонов
    // Редактирование группы шаблонов
    "classgroup.edit": ADMIN_PATH + "class/index.php?phase=1&ClassGroup=%1",
    "classgroup_fs.edit": ADMIN_PATH + "class/index.php?fs=1&phase=1&ClassGroup=%1",


    // ---------------------Работа с шаблонами-------------------------------
    // Вывод списка шаблонов
    "dataclass.list": ADMIN_PATH + "class/",
    "dataclass_fs.list": ADMIN_PATH + "class/?fs=1",

    // Информация о шаблоне
    "dataclass.info": ADMIN_PATH + "class/index.php?phase=13&ClassID=%1",
    "dataclass_fs.info": ADMIN_PATH + "class/index.php?fs=1&phase=13&ClassID=%1",

    // Добавление шаблона
    "dataclass.add": ADMIN_PATH + "class/index.php?phase=10&ClassGroup=%1",
    "dataclass_fs.add": ADMIN_PATH + "class/index.php?fs=1&phase=10&ClassGroup=%1",

    // Редактирование шаблона
    "dataclass.edit": ADMIN_PATH + "class/index.php?phase=4&ClassID=%1",
    "dataclass_fs.edit": ADMIN_PATH + "class/index.php?fs=1&phase=4&ClassID=%1",

    // Удаление шаблона
    "dataclass.delete": ADMIN_PATH + "class/index.php?phase=6&Delete%1=%1",
    "dataclass_fs.delete": ADMIN_PATH + "class/index.php?fs=1&phase=6&Delete%1=%1",

    // Шаблоны действий
    "dataclass.classaction": ADMIN_PATH + "class/index.php?phase=8&myaction=1&ClassID=%1",
    "dataclass_fs.classaction": ADMIN_PATH + "class/index.php?fs=1&phase=8&myaction=1&ClassID=%1",

    // Редактирование альтернативного шаблона добавления
    "dataclass.customadd": ADMIN_PATH + "class/index.php?phase=8&myaction=1&ClassID=%1",
    "dataclass_fs.customadd": ADMIN_PATH + "class/index.php?fs=1&phase=8&myaction=1&ClassID=%1",

    // Редактирование альтернативного шаблона изменения
    "dataclass.customedit": ADMIN_PATH + "class/index.php?phase=8&myaction=2&ClassID=%1",
    "dataclass_fs.customedit": ADMIN_PATH + "class/index.php?fs=1&phase=8&myaction=2&ClassID=%1",

    // Редактирование альтернативного шаблона поиска
    "dataclass.customsearch": ADMIN_PATH + "class/index.php?phase=8&myaction=3&ClassID=%1",
    "dataclass_fs.customsearch": ADMIN_PATH + "class/index.php?fs=1&phase=8&myaction=3&ClassID=%1",

    // Редактирование альтернативного шаблона подписки
    // Редактирование альтернативного шаблона удаления
    "dataclass.customdelete": ADMIN_PATH + "class/index.php?phase=8&myaction=5&ClassID=%1",
    "dataclass_fs.customdelete": ADMIN_PATH + "class/index.php?fs=1&phase=8&myaction=5&ClassID=%1",

    // Список полей шаблона
    "dataclass.fields": ADMIN_PATH + "field/index.php?ClassID=%1",
    "dataclass_fs.fields": ADMIN_PATH + "field/index.php?fs=1&ClassID=%1",

    // Пользовательские настройки
    "dataclass.custom": ADMIN_PATH + "class/index.php?phase=24&ClassID=%1",
    "dataclass_fs.custom": ADMIN_PATH + "class/index.php?fs=1&phase=24&ClassID=%1",

    // Редактирование одной настройки
    "dataclass.custom.edit": ADMIN_PATH + "class/index.php?phase=25&ClassID=%1&param=%2",
    "dataclass_fs.custom.edit": ADMIN_PATH + "class/index.php?fs=1&phase=25&ClassID=%1&param=%2",

    // Создание новой настройки
    "dataclass.custom.new": ADMIN_PATH + "class/index.php?phase=25&ClassID=%1",
    "dataclass_fs.custom.new": ADMIN_PATH + "class/index.php?fs=1&phase=25&ClassID=%1",

    // Ручное редактирование
    "dataclass.custom.manual": ADMIN_PATH + "class/index.php?phase=26&ClassID=%1",
    "dataclass_fs.custom.manual": ADMIN_PATH + "class/index.php?fs=1&phase=26&ClassID=%1",
    "dataclass_fs.custom_fs.edit": ADMIN_PATH + "class/index.php?fs=1&phase=25&ClassID=%1&param=%2",
    "dataclass_fs.custom_fs.new": ADMIN_PATH + "class/index.php?fs=1&phase=25&ClassID=%1",
    "dataclass_fs.custom_fs.manual": ADMIN_PATH + "class/index.php?fs=1&phase=26&ClassID=%1",

    // Импорт шаблона
    "dataclass.import": ADMIN_PATH + "class/import.php?ClassGroup=%1",
    "dataclass_fs.import": ADMIN_PATH + "backup.php?type=class&mode=import&ClassGroup=%1",

    // Конвертирование шаблона 4->5
    "dataclass.convert": ADMIN_PATH + "class/convert.php?fs=0&ClassID=%1",

    // Отмена конвертирования шаблона 4->5
    "dataclass_fs.convertundo": ADMIN_PATH + "class/convert.php?fs=1&ClassID=%1&phase=3",

    // Мастер создания шаблона
    "dataclass.wizard": ADMIN_PATH + "wizard/wizard_class.php?phase=%1&Class_Type=%2&ClassID=%3",
    "dataclass_fs.wizard": ADMIN_PATH + "wizard/wizard_class.php?fs=1&phase=%1&Class_Type=%2&ClassID=%3",


    // -----------------------Работа с шаблонами компонентов----------------------
    // Редактирование группы шаблонов компонента
    "classtemplates.edit": ADMIN_PATH + "class/index.php?phase=20&ClassID=%1",
    "classtemplates_fs.edit": ADMIN_PATH + "class/index.php?fs=1&phase=20&ClassID=%1",

    // Добавление шаблона компонента
    "classtemplate.add": ADMIN_PATH + "class/index.php?phase=14&ClassID=%1",
    "classtemplate_fs.add": ADMIN_PATH + "class/index.php?fs=1&phase=14&ClassID=%1",

    // Инфо и Редактирование настроек шаблона компонента
    "classtemplate.info": ADMIN_PATH + "class/index.php?phase=131&ClassID=%1",
    "classtemplate_fs.info": ADMIN_PATH + "class/index.php?fs=1&phase=131&ClassID=%1",

    // Редактирование шаблона компонента
    "classtemplate.edit": ADMIN_PATH + "class/index.php?phase=16&ClassID=%1",
    "classtemplate_fs.edit": ADMIN_PATH + "class/index.php?fs=1&phase=16&ClassID=%1",

    // Удаление шаблона компонента
    "classtemplate.delete": ADMIN_PATH + "class/index.php?phase=18&Delete%1=%1&ClassTemplate=%2",
    "classtemplate_fs.delete": ADMIN_PATH + "class/index.php?fs=1&phase=18&Delete%1=%1&ClassTemplate=%2",

    // Шаблоны действий
    "classtemplate.classaction": ADMIN_PATH + "class/index.php?phase=22&myaction=1&ClassID=%1",
    "classtemplate_fs.classaction": ADMIN_PATH + "class/index.php?fs=1&phase=22&myaction=1&ClassID=%1",

    // Редактирование альтернативного блока добавления
    "classtemplate.customadd": ADMIN_PATH + "class/index.php?phase=22&myaction=1&ClassID=%1",
    "classtemplate_fs.customadd": ADMIN_PATH + "class/index.php?fs=1&phase=22&myaction=1&ClassID=%1",

    // Редактирование альтернативного блока изменения
    "classtemplate.customedit": ADMIN_PATH + "class/index.php?phase=22&myaction=2&ClassID=%1",
    "classtemplate_fs.customedit": ADMIN_PATH + "class/index.php?fs=1&phase=22&myaction=2&ClassID=%1",

    // Редактирование альтернативного блока поиска
    "classtemplate.customsearch": ADMIN_PATH + "class/index.php?phase=22&myaction=3&ClassID=%1",
    "classtemplate_fs.customsearch": ADMIN_PATH + "class/index.php?fs=1&phase=22&myaction=3&ClassID=%1",

    // Редактирование альтернативного блока подписки
    // Редактирование альтернативного блока удаления
    "classtemplate.customdelete": ADMIN_PATH + "class/index.php?phase=22&myaction=5&ClassID=%1",
    "classtemplate_fs.customdelete": ADMIN_PATH + "class/index.php?fs=1&phase=22&myaction=5&ClassID=%1",

    // Пользовательские настройки
    "classtemplate.custom": ADMIN_PATH + "class/index.php?phase=240&ClassID=%1",
    "classtemplate_fs.custom": ADMIN_PATH + "class/index.php?fs=1&phase=240&ClassID=%1",

    // Редактирование одной настройки
    "classtemplate.custom.edit": ADMIN_PATH + "class/index.php?phase=250&ClassID=%1&param=%2",
    "classtemplate_fs.custom.edit": ADMIN_PATH + "class/index.php?fs=1&phase=250&ClassID=%1&param=%2",

    // Создание новой настройки
    "classtemplate.custom.new": ADMIN_PATH + "class/index.php?phase=250&ClassID=%1",
    "classtemplate_fs.custom.new": ADMIN_PATH + "class/index.php?fs=1&phase=250&ClassID=%1",

    // Ручное редактирование
    "classtemplate.custom.manual": ADMIN_PATH + "class/index.php?phase=260&ClassID=%1",
    "classtemplate_fs.custom.manual": ADMIN_PATH + "class/index.php?fs=1&phase=260&ClassID=%1",

    "classtemplate_fs.custom_fs.edit": ADMIN_PATH + "class/index.php?fs=1&phase=250&ClassID=%1&param=%2",
    "classtemplate_fs.custom_fs.new": ADMIN_PATH + "class/index.php?fs=1&phase=250&ClassID=%1",
    "classtemplate_fs.custom_fs.manual": ADMIN_PATH + "class/index.php?fs=1&phase=260&ClassID=%1",


    // ------------------------Работа с виджетами-------------------
    "widgetclass.list": ADMIN_PATH + "widget/",
    "widgetclass_fs.list": ADMIN_PATH + "widget/?fs=1",
    "widgetclass.add": ADMIN_PATH + "widget/index.php?phase=20&widget_group=%1",
    "widgetclass_fs.add": ADMIN_PATH + "widget/index.php?fs=1&phase=20&widget_group=%1",
    "widgetclass.edit": ADMIN_PATH + "widget/index.php?phase=30&widgetclass_id=%1",
    "widgetclass_fs.edit": ADMIN_PATH + "widget/index.php?fs=1&phase=30&widgetclass_id=%1",

    "widgetclass.info": ADMIN_PATH + "widget/index.php?phase=40&widgetclass_id=%1",
    "widgetclass.action": ADMIN_PATH + "widget/index.php?phase=50&widgetclass_id=%1",
    "widgetclass.drop": ADMIN_PATH + "widget/index.php?phase=60&widgetclass_id=%1&from_tree=%2",
    "widgetclass.fields": ADMIN_PATH + "field/index.php?widgetclass_id=%1",
    "widgetclass.import": NETCAT_PATH + "action.php?ctrl=admin.backup&action=import",

    "widgetclass_fs.info": ADMIN_PATH + "widget/index.php?fs=1&phase=40&widgetclass_id=%1",
    "widgetclass_fs.action": ADMIN_PATH + "widget/index.php?fs=1&phase=50&widgetclass_id=%1",
    "widgetclass_fs.drop": ADMIN_PATH + "widget/index.php?fs=1&phase=60&widgetclass_id=%1&from_tree=%2",
    "widgetclass_fs.fields": ADMIN_PATH + "field/index.php?isWidget=1&fs=1&widgetclass_id=%1",
    "widgetclass_fs.import": NETCAT_PATH + "action.php?ctrl=admin.backup&action=import",

    "widgetgroup.edit": ADMIN_PATH + "widget/index.php?phase=10&category=%1",
    "widgetfield.add": ADMIN_PATH + "field/index.php?isWidget=1&phase=2&widgetclass_id=%1",
    "widgetfield.edit": ADMIN_PATH + "field/index.php?isWidget=1&phase=4&FieldID=%1&widgetclass_id=%2",
    "widgetfield.delete": ADMIN_PATH + "field/index.php?isWidget=1&phase=6&widgetclass_id=%1&Delete[]=%2",
    "widgetgroup_fs.edit": ADMIN_PATH + "widget/index.php?fs=1&phase=10&category=%1",
    "widgetfield_fs.add": ADMIN_PATH + "field/index.php?isWidget=1&fs=1&phase=2&widgetclass_id=%1",
    "widgetfield_fs.edit": ADMIN_PATH + "field/index.php?isWidget=1&fs=1&phase=4&FieldID=%1&widgetclass_id=%2",
    "widgetfield_fs.delete": ADMIN_PATH + "field/index.php?isWidget=1&fs=1&phase=6&widgetclass_id=%1&Delete[]=%2",
    "widgets": ADMIN_PATH + "widget/admin.php",
    "widgets.add": ADMIN_PATH + "widget/admin.php?phase=20&widget_id=%1",
    "widgets.edit": ADMIN_PATH + "widget/admin.php?phase=30&widget_id=%1",
    "widgets.delete": ADMIN_PATH + "widget/admin.php?phase=60&widget_id=%1",


    // ------------------------Работа с системными таблицами-----------------------
    // Список системных таблиц
    "systemclass.list": ADMIN_PATH + "field/system.php",
    "systemclass_fs.list": ADMIN_PATH + "field/system.php?fs=1",

    // Редактирование системной таблицы
    "systemclass.edit": ADMIN_PATH + "field/system.php?phase=2&SystemTableID=%1",
    "systemclass_fs.edit": ADMIN_PATH + "field/system.php?fs=1&phase=2&SystemTableID=%1",

    // Редактирование альтернативного шаблона добавления
    "systemclass.customadd": ADMIN_PATH + "field/system.php?phase=4&myaction=1&SystemTableID=%1",
    "systemclass_fs.customadd": ADMIN_PATH + "field/system.php?fs=1&phase=4&myaction=1&SystemTableID=%1",

    // Редактирование альтернативного шаблона измениения
    "systemclass.customedit": ADMIN_PATH + "field/system.php?phase=4&myaction=2&SystemTableID=%1",
    "systemclass_fs.customedit": ADMIN_PATH + "field/system.php?fs=1&phase=4&myaction=2&SystemTableID=%1",

    // Редактирование альтернативного шаблона поиска
    "systemclass.customsearch": ADMIN_PATH + "field/system.php?phase=4&myaction=3&SystemTableID=%1",
    "systemclass_fs.customsearch": ADMIN_PATH + "field/system.php?fs=1&phase=4&myaction=3&SystemTableID=%1",

    // Список полей шаблона
    "systemclass.fields": ADMIN_PATH + "field/index.php?isSys=1&SystemTableID=%1",
    "systemclass_fs.fields": ADMIN_PATH + "field/index.php?fs=1&isSys=1&SystemTableID=%1",

    // ------------------------Работа с полями шаблона---------------------
    // Добавление поля шаблона
    "field.add": ADMIN_PATH + "field/index.php?phase=2&ClassID=%1",
    "field_fs.add": ADMIN_PATH + "field/index.php?fs=1&phase=2&ClassID=%1",

    // Редактирование поля шаблона
    "field.edit": ADMIN_PATH + "field/index.php?phase=4&FieldID=%1",
    "field_fs.edit": ADMIN_PATH + "field/index.php?fs=1&phase=4&FieldID=%1",

    // Удаление поля шаблона
    "field.delete": ADMIN_PATH + "field/index.php?phase=6&ClassID=%1&Delete[]=%2",
    "field_fs.delete": ADMIN_PATH + "field/index.php?fs=1&phase=6&ClassID=%1&Delete[]=%2",

    // --------------Работа с системными полями--------------------
    // Добавление поля шаблона
    "systemfield.add": ADMIN_PATH + "field/index.php?phase=2&isSys=1&SystemTableID=%1",
    "systemfield_fs.add": ADMIN_PATH + "field/index.php?fs=1&phase=2&isSys=1&SystemTableID=%1",

    // Редактирование поля шаблона
    "systemfield.edit": ADMIN_PATH + "field/index.php?phase=4&isSys=1&FieldID=%1",
    "systemfield_fs.edit": ADMIN_PATH + "field/index.php?fs=1&phase=4&isSys=1&FieldID=%1",

    // Удаление поля шаблона
    "systemfield.delete": ADMIN_PATH + "field/index.php?phase=6&isSys=1&SystemTableID=%1&Delete[]=%2",
    "systemfield_fs.delete": ADMIN_PATH + "field/index.php?fs=1&phase=6&isSys=1&SystemTableID=%1&Delete[]=%2",


    // ---------------------Работа со списками-------------------
    // Вывод списка
    "classificator.list": ADMIN_PATH + "classificator.php",

    // Добавление списка
    "classificator.add": ADMIN_PATH + "classificator.php?phase=1",

    // Редактирование списка
    "classificator.edit": ADMIN_PATH + "classificator.php?phase=4&ClassificatorID=%1",

    // Редактирование пользовательских настроек списка
    "classificator.custom": NETCAT_PATH + "action.php?ctrl=admin.list&action=custom&classificator_id=%1",

    // Добавление поля в пользовательские настройки списка
    "classificator.custom.add": NETCAT_PATH + "action.php?ctrl=admin.list&action=custom_add&classificator_id=%1",

    // Редактирование поля в пользовательских настройках списка
    "classificator.custom.edit": NETCAT_PATH + "action.php?ctrl=admin.list&action=custom_edit&classificator_id=%1&param=%2",

    // Ручное редактирование пользовательских настроек списка
    "classificator.custom.manual": NETCAT_PATH + "action.php?ctrl=admin.list&action=custom_manual&classificator_id=%1",

    // Удаление списка
    "classificator.delete": ADMIN_PATH + "classificator.php?phase=3&Delete%1=%1",

    // Импорт списка
    "classificator.import": ADMIN_PATH + "classificator.php?phase=12",

    // Редактирование элемента списка
    "classificator.item.edit": ADMIN_PATH + "classificator.php?phase=10&ClassificatorID=%1&IdInClassificator=%2",

    // Добавление элемента списка
    "classificator.item.add": ADMIN_PATH + "classificator.php?phase=8&ClassificatorID=%1",


    // ------------------Работа с макетами------------------
    "template.list": ADMIN_PATH + "template/index.php",
    "template_fs.list": ADMIN_PATH + "template/index.php?fs=1",

    // Добавление макета
    "template.add": ADMIN_PATH + "template/index.php?phase=20&ParentTemplateID=%1",
    "template_fs.add": ADMIN_PATH + "template/index.php?fs=1&phase=20&ParentTemplateID=%1",

    // Редактирование макета
    "template.edit": ADMIN_PATH + "template/index.php?phase=4&TemplateID=%1",
    "template_fs.edit": ADMIN_PATH + "template/index.php?fs=1&phase=4&TemplateID=%1",

    // Удаление макета
    "template.delete": ADMIN_PATH + "template/index.php?phase=6&Delete%1=%1",

    "template_fs.delete": ADMIN_PATH + "template/index.php?fs=1&phase=6&Delete%1=%1",
    // Импорт макета
    "template.import": ADMIN_PATH + "template/import.php?TemplateID=%1",
    "template_fs.import": ADMIN_PATH + "template/import.php?fs=1&TemplateID=%1",

    // Пользовательские настройки
    "template.custom": ADMIN_PATH + "template/index.php?phase=8&TemplateID=%1",
    "template_fs.custom": ADMIN_PATH + "template/index.php?fs=1&phase=8&TemplateID=%1",

    // Редактирование одной настройки
    "template.custom.edit": ADMIN_PATH + "template/index.php?phase=9&TemplateID=%1&param=%2",
    "template_fs.custom.edit": ADMIN_PATH + "template/index.php?fs=1&phase=9&TemplateID=%1&param=%2",

    // Создание новой настройки
    "template.custom.new": ADMIN_PATH + "template/index.php?phase=9&TemplateID=%1",
    "template_fs.custom.new": ADMIN_PATH + "template/index.php?fs=1&phase=9&TemplateID=%1",

    // Ручное редактирование
    "template.custom.manual": ADMIN_PATH + "template/index.php?phase=10&TemplateID=%1",
    "template_fs.custom.manual": ADMIN_PATH + "template/index.php?fs=1&phase=10&TemplateID=%1",
    "template_fs.custom_fs.edit": ADMIN_PATH + "template/index.php?fs=1&phase=9&TemplateID=%1&param=%2",
    "template_fs.custom_fs.new": ADMIN_PATH + "template/index.php?fs=1&phase=9&TemplateID=%1",
    "template_fs.custom_fs.manual": ADMIN_PATH + "template/index.php?fs=1&phase=10&TemplateID=%1",

    // Partials
    "template_fs.partials_list": NETCAT_PATH + "action.php?ctrl=admin.template_partials&action=list&fs=1&TemplateID=%1",
    "template_fs.partials_add": NETCAT_PATH + "action.php?ctrl=admin.template_partials&action=add&fs=1&TemplateID=%1",
    "template_fs.partials_edit": NETCAT_PATH + "action.php?ctrl=admin.template_partials&action=edit&fs=1&TemplateID=%1&partial=%2",
    "template_fs.partials_remove": NETCAT_PATH + "action.php?ctrl=admin.template_partials&action=remove&fs=1&TemplateID=%1&partial=%2",

    // Стандартные модули
    // адреса для пользовательских модулей могут быть заданы в файле modules/имяМодуля/url_routes.js:
    // urlDispatcher.route['url'] = 'path';
    "module.auth": NETCAT_PATH + "modules/auth/admin.php",
    "module.banner": NETCAT_PATH + "modules/banner/admin.php",
    "module.forum": NETCAT_PATH + "modules/forum/admin.php",
    "module.linkmanager": NETCAT_PATH + "modules/linkmanager/admin.php?page=%1",
    "module.netshop": NETCAT_PATH + "modules/netshop/admin.php",
    "module.search": NETCAT_PATH + "modules/search/admin.php?page=%1",
    "module.searchold": NETCAT_PATH + "modules/searchold/admin.php?page=%1",
    "module.stats": NETCAT_PATH + "modules/stats/admin.php?phase=9&cat_id=%1",
    "module.openstat": NETCAT_PATH + "modules/stats/admin.php?phase=11",
    "module.subscriber": NETCAT_PATH + "modules/subscriber/admin.php",
    "module.tagscloud": NETCAT_PATH + "modules/tagscloud/admin.php",
    "module.blog": NETCAT_PATH + "modules/blog/admin.php",
    "module.calendar": NETCAT_PATH + "modules/calendar/admin.php",

    // Действия с пользователями
    "user.list": ADMIN_PATH + "user/",
    "user.add": ADMIN_PATH + "user/register.php",
    "user.edit": ADMIN_PATH + "user/index.php?phase=4&UserID=%1",
    "user.password": ADMIN_PATH + "user/index.php?phase=6&UserID=%1",
    "user.rights": ADMIN_PATH + "user/index.php?phase=8&UserID=%1",
    "user.subscribers": ADMIN_PATH + "user/index.php?phase=15&UserID=%1",
    "user.support": ADMIN_PATH + "user/index.php?phase=30&duration=%1",

    // Рассылка по базе
    "user.mail": ADMIN_PATH + "user/MessageToAll.php",

    // Действия с группами пользователей
    "usergroup.list": ADMIN_PATH + "user/group.php",
    "usergroup.add": ADMIN_PATH + "user/group.php?phase=5",
    "usergroup.edit": ADMIN_PATH + "user/group.php?phase=3&PermissionGroupID=%1",
    "usergroup.rights": ADMIN_PATH + "user/group.php?phase=8&PermissionGroupID=%1",

    // Инструменты
    "tools.sql": ADMIN_PATH + "sql/index.php",
    "tools.usermail": ADMIN_PATH + "user/MessageToAll.php",
    "tools.backup": ADMIN_PATH + "dump.php?phase=%1",
    "tools.databackup": ADMIN_PATH + "backup.php?mode=export",
    "tools.databackup.export": NETCAT_PATH + "action.php?ctrl=admin.backup&action=export",
    "tools.databackup.import": NETCAT_PATH + "action.php?ctrl=admin.backup&action=import",
    "tools.csv.export": NETCAT_PATH + "action.php?ctrl=admin.csv.csv&action=export",
    "tools.csv.import": NETCAT_PATH + "action.php?ctrl=admin.csv.csv&action=import",
    "tools.csv.delete": NETCAT_PATH + "action.php?ctrl=admin.csv.csv&action=delete&file=%1",
    "tools.csv.import_history": NETCAT_PATH + "action.php?ctrl=admin.csv.csv&action=import_history",
    "tools.csv.rollback": NETCAT_PATH + "action.php?ctrl=admin.csv.csv&action=rollback&id=%1",
    "tools.patch": ADMIN_PATH + "patch/?phase=%1",
    "tools.activation": ADMIN_PATH + "patch/activation.php?phase=%1",
    "tools.installmodule": ADMIN_PATH + "modules/index.php?phase=5",
    "tools.totalstat": ADMIN_PATH + "report/index.php?phase=%1",
    //'tools.lastchanges': ADMIN_PATH + 'report/last.php',
    "tools.systemmessages": ADMIN_PATH + "report/system.php",
    //'tools.reportstatus': ADMIN_PATH + 'report/report.php',
    "tools.html": ADMIN_PATH + "html/",
    "tools.copy": ADMIN_PATH + "subdivision/copy.php?copy_type=%1&catalogue_id=%2&sub_id=%3",
    "trash.list": ADMIN_PATH + "trash/index.php",
    "trash.settings": ADMIN_PATH + "trash/index.php?phase=3",

    // Справка
    "help.about": ADMIN_PATH + "about/index.php",

    // Настройки системы
    "system.settings": ADMIN_PATH + "settings.php?phase=1",
    "system.edit": ADMIN_PATH + "settings.php?phase=1",

    //Настройки WYSIWYG
    "wysiwyg.ckeditor.settings": ADMIN_PATH + "wysiwyg/index.php?editor=ckeditor",
    "wysiwyg.ckeditor.panels": ADMIN_PATH + "wysiwyg/index.php?phase=3",
    "wysiwyg.ckeditor.panels.add": ADMIN_PATH + "wysiwyg/index.php?phase=4",
    "wysiwyg.ckeditor.panels.edit": ADMIN_PATH + "wysiwyg/index.php?phase=5&Wysiwyg_Panel_ID=%1",
    "wysiwyg.fckeditor.settings": ADMIN_PATH + "wysiwyg/index.php?editor=fckeditor",

    // Переадресации
    "redirect.list": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=list&group=%1",
    "redirect.add": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=edit&group=%1",
    "redirect.edit": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=edit&id=%1",
    "redirect.delete": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=delete&dgroup=%1",
    "redirect.group.add": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=edit_group",
    "redirect.group.edit": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=edit_group&group=%1",
    "redirect.import": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=import",

    // Управление задачами
    "cron.settings": ADMIN_PATH + "crontasks.php",
    "cron.add": ADMIN_PATH + "crontasks.php?phase=1",
    "cron.edit": ADMIN_PATH + "crontasks.php?phase=4&CronID=%1",

    // Модули
    "module.list": ADMIN_PATH + "modules/index.php",
    "module.settings": ADMIN_PATH + "modules/index.php?phase=2&module_name=%1",
    "modules.settings": ADMIN_PATH + "modules/index.php?phase=2&module_name=%1", // SAME

    // Избранное
    "favorite.other": ADMIN_PATH + "subdivision/full.php?CatalogueID=%1",
    "favorite.add": ADMIN_PATH + "subdivision/favorites.php?phase=1",
    "favorite.list": ADMIN_PATH + "subdivision/favorites.php?phase=1",

    // Безопасность

    'security.filter.settings': NETCAT_PATH + 'action.php?ctrl=security_filter_admin_settings&action=show_settings&site_id=%1',
    'security.filter.log': NETCAT_PATH + 'action.php?ctrl=security_filter_admin_log&action=show_log',
    'security.filter.log.record': NETCAT_PATH + 'action.php?ctrl=security_filter_admin_log&action=log_record&security_filterlog_id=%1',
    'security.filter.log.group': NETCAT_PATH + 'action.php?ctrl=security_filter_admin_log&action=show_log&filter[Hash]=%1',

    'security.auth.captcha': NETCAT_PATH + 'action.php?ctrl=security_admin_captcha&action=show_settings&site_id=%1',
    'security.auth.2fa': NETCAT_PATH + 'action.php?ctrl=security_2fa_admin&action=show_settings&site_id=%1',

    // Диагностика сайта
    "selftest": NETCAT_PATH + "action.php?ctrl=selftest&action=index",

    1: "" // dummy entry
};

/*
 json2.js
 2013-05-26

 Public Domain.

 NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.

 See http://www.JSON.org/js.html


 This code should be minified before deployment.
 See http://javascript.crockford.com/jsmin.html

 USE YOUR OWN COPY. IT IS EXTREMELY UNWISE TO LOAD CODE FROM SERVERS YOU DO
 NOT CONTROL.


 This file creates a global JSON object containing two methods: stringify
 and parse.

 JSON.stringify(value, replacer, space)
 value       any JavaScript value, usually an object or array.

 replacer    an optional parameter that determines how object
 values are stringified for objects. It can be a
 function or an array of strings.

 space       an optional parameter that specifies the indentation
 of nested structures. If it is omitted, the text will
 be packed without extra whitespace. If it is a number,
 it will specify the number of spaces to indent at each
 level. If it is a string (such as '\t' or '&nbsp;'),
 it contains the characters used to indent at each level.

 This method produces a JSON text from a JavaScript value.

 When an object value is found, if the object contains a toJSON
 method, its toJSON method will be called and the result will be
 stringified. A toJSON method does not serialize: it returns the
 value represented by the name/value pair that should be serialized,
 or undefined if nothing should be serialized. The toJSON method
 will be passed the key associated with the value, and this will be
 bound to the value

 For example, this would serialize Dates as ISO strings.

 Date.prototype.toJSON = function (key) {
 function f(n) {
 // Format integers to have at least two digits.
 return n < 10 ? '0' + n : n;
 }

 return this.getUTCFullYear()   + '-' +
 f(this.getUTCMonth() + 1) + '-' +
 f(this.getUTCDate())      + 'T' +
 f(this.getUTCHours())     + ':' +
 f(this.getUTCMinutes())   + ':' +
 f(this.getUTCSeconds())   + 'Z';
 };

 You can provide an optional replacer method. It will be passed the
 key and value of each member, with this bound to the containing
 object. The value that is returned from your method will be
 serialized. If your method returns undefined, then the member will
 be excluded from the serialization.

 If the replacer parameter is an array of strings, then it will be
 used to select the members to be serialized. It filters the results
 such that only members with keys listed in the replacer array are
 stringified.

 Values that do not have JSON representations, such as undefined or
 functions, will not be serialized. Such values in objects will be
 dropped; in arrays they will be replaced with null. You can use
 a replacer function to replace those with JSON values.
 JSON.stringify(undefined) returns undefined.

 The optional space parameter produces a stringification of the
 value that is filled with line breaks and indentation to make it
 easier to read.

 If the space parameter is a non-empty string, then that string will
 be used for indentation. If the space parameter is a number, then
 the indentation will be that many spaces.

 Example:

 text = JSON.stringify(['e', {pluribus: 'unum'}]);
 // text is '["e",{"pluribus":"unum"}]'


 text = JSON.stringify(['e', {pluribus: 'unum'}], null, '\t');
 // text is '[\n\t"e",\n\t{\n\t\t"pluribus": "unum"\n\t}\n]'

 text = JSON.stringify([new Date()], function (key, value) {
 return this[key] instanceof Date ?
 'Date(' + this[key] + ')' : value;
 });
 // text is '["Date(---current time---)"]'


 JSON.parse(text, reviver)
 This method parses a JSON text to produce an object or array.
 It can throw a SyntaxError exception.

 The optional reviver parameter is a function that can filter and
 transform the results. It receives each of the keys and values,
 and its return value is used instead of the original value.
 If it returns what it received, then the structure is not modified.
 If it returns undefined then the member is deleted.

 Example:

 // Parse the text. Values that look like ISO date strings will
 // be converted to Date objects.

 myData = JSON.parse(text, function (key, value) {
 var a;
 if (typeof value === 'string') {
 a =
 /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2}(?:\.\d*)?)Z$/.exec(value);
 if (a) {
 return new Date(Date.UTC(+a[1], +a[2] - 1, +a[3], +a[4],
 +a[5], +a[6]));
 }
 }
 return value;
 });

 myData = JSON.parse('["Date(09/09/2001)"]', function (key, value) {
 var d;
 if (typeof value === 'string' &&
 value.slice(0, 5) === 'Date(' &&
 value.slice(-1) === ')') {
 d = new Date(value.slice(5, -1));
 if (d) {
 return d;
 }
 }
 return value;
 });


 This is a reference implementation. You are free to copy, modify, or
 redistribute.
 */

/*jslint evil: true, regexp: true */

/*members "", "\b", "\t", "\n", "\f", "\r", "\"", JSON, "\\", apply,
 call, charCodeAt, getUTCDate, getUTCFullYear, getUTCHours,
 getUTCMinutes, getUTCMonth, getUTCSeconds, hasOwnProperty, join,
 lastIndex, length, parse, prototype, push, replace, slice, stringify,
 test, toJSON, toString, valueOf
 */


// Create a JSON object only if one does not already exist. We create the
// methods in a closure to avoid creating global variables.

if (typeof JSON !== 'object') {
    JSON = {};
}

(function () {
    'use strict';

    function f(n) {
        // Format integers to have at least two digits.
        return n < 10 ? '0' + n : n;
    }

    if (typeof Date.prototype.toJSON !== 'function') {

        Date.prototype.toJSON = function () {

            return isFinite(this.valueOf())
                ? this.getUTCFullYear()     + '-' +
                f(this.getUTCMonth() + 1) + '-' +
                f(this.getUTCDate())      + 'T' +
                f(this.getUTCHours())     + ':' +
                f(this.getUTCMinutes())   + ':' +
                f(this.getUTCSeconds())   + 'Z'
                : null;
        };

        String.prototype.toJSON      =
            Number.prototype.toJSON  =
                Boolean.prototype.toJSON = function () {
                    return this.valueOf();
                };
    }

    var cx = /[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
        escapable = /[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
        gap,
        indent,
        meta = {    // table of character substitutions
            '\b': '\\b',
            '\t': '\\t',
            '\n': '\\n',
            '\f': '\\f',
            '\r': '\\r',
            '"' : '\\"',
            '\\': '\\\\'
        },
        rep;


    function quote(string) {

// If the string contains no control characters, no quote characters, and no
// backslash characters, then we can safely slap some quotes around it.
// Otherwise we must also replace the offending characters with safe escape
// sequences.

        escapable.lastIndex = 0;
        return escapable.test(string) ? '"' + string.replace(escapable, function (a) {
            var c = meta[a];
            return typeof c === 'string'
                ? c
                : '\\u' + ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
        }) + '"' : '"' + string + '"';
    }


    function str(key, holder) {

// Produce a string from holder[key].

        var i,          // The loop counter.
            k,          // The member key.
            v,          // The member value.
            length,
            mind = gap,
            partial,
            value = holder[key];

// If the value has a toJSON method, call it to obtain a replacement value.

        if (value && typeof value === 'object' &&
            typeof value.toJSON === 'function') {
            value = value.toJSON(key);
        }

// If we were called with a replacer function, then call the replacer to
// obtain a replacement value.

        if (typeof rep === 'function') {
            value = rep.call(holder, key, value);
        }

// What happens next depends on the value's type.

        switch (typeof value) {
            case 'string':
                return quote(value);

            case 'number':

// JSON numbers must be finite. Encode non-finite numbers as null.

                return isFinite(value) ? String(value) : 'null';

            case 'boolean':
            case 'null':

// If the value is a boolean or null, convert it to a string. Note:
// typeof null does not produce 'null'. The case is included here in
// the remote chance that this gets fixed someday.

                return String(value);

// If the type is 'object', we might be dealing with an object or an array or
// null.

            case 'object':

// Due to a specification blunder in ECMAScript, typeof null is 'object',
// so watch out for that case.

                if (!value) {
                    return 'null';
                }

// Make an array to hold the partial results of stringifying this object value.

                gap += indent;
                partial = [];

// Is the value an array?

                if (Object.prototype.toString.apply(value) === '[object Array]') {

// The value is an array. Stringify every element. Use null as a placeholder
// for non-JSON values.

                    length = value.length;
                    for (i = 0; i < length; i += 1) {
                        partial[i] = str(i, value) || 'null';
                    }

// Join all of the elements together, separated with commas, and wrap them in
// brackets.

                    v = partial.length === 0
                        ? '[]'
                        : gap
                        ? '[\n' + gap + partial.join(',\n' + gap) + '\n' + mind + ']'
                        : '[' + partial.join(',') + ']';
                    gap = mind;
                    return v;
                }

// If the replacer is an array, use it to select the members to be stringified.

                if (rep && typeof rep === 'object') {
                    length = rep.length;
                    for (i = 0; i < length; i += 1) {
                        if (typeof rep[i] === 'string') {
                            k = rep[i];
                            v = str(k, value);
                            if (v) {
                                partial.push(quote(k) + (gap ? ': ' : ':') + v);
                            }
                        }
                    }
                } else {

// Otherwise, iterate through all of the keys in the object.

                    for (k in value) {
                        if (Object.prototype.hasOwnProperty.call(value, k)) {
                            v = str(k, value);
                            if (v) {
                                partial.push(quote(k) + (gap ? ': ' : ':') + v);
                            }
                        }
                    }
                }

// Join all of the member texts together, separated with commas,
// and wrap them in braces.

                v = partial.length === 0
                    ? '{}'
                    : gap
                    ? '{\n' + gap + partial.join(',\n' + gap) + '\n' + mind + '}'
                    : '{' + partial.join(',') + '}';
                gap = mind;
                return v;
        }
    }

// If the JSON object does not yet have a stringify method, give it one.

    if (typeof JSON.stringify !== 'function') {
        JSON.stringify = function (value, replacer, space) {

// The stringify method takes a value and an optional replacer, and an optional
// space parameter, and returns a JSON text. The replacer can be a function
// that can replace values, or an array of strings that will select the keys.
// A default replacer method can be provided. Use of the space parameter can
// produce text that is more easily readable.

            var i;
            gap = '';
            indent = '';

// If the space parameter is a number, make an indent string containing that
// many spaces.

            if (typeof space === 'number') {
                for (i = 0; i < space; i += 1) {
                    indent += ' ';
                }

// If the space parameter is a string, it will be used as the indent string.

            } else if (typeof space === 'string') {
                indent = space;
            }

// If there is a replacer, it must be a function or an array.
// Otherwise, throw an error.

            rep = replacer;
            if (replacer && typeof replacer !== 'function' &&
                (typeof replacer !== 'object' ||
                    typeof replacer.length !== 'number')) {
                throw new Error('JSON.stringify');
            }

// Make a fake root object containing our value under the key of ''.
// Return the result of stringifying the value.

            return str('', {'': value});
        };
    }


// If the JSON object does not yet have a parse method, give it one.

    if (typeof JSON.parse !== 'function') {
        JSON.parse = function (text, reviver) {

// The parse method takes a text and an optional reviver function, and returns
// a JavaScript value if the text is a valid JSON text.

            var j;

            function walk(holder, key) {

// The walk method is used to recursively walk the resulting structure so
// that modifications can be made.

                var k, v, value = holder[key];
                if (value && typeof value === 'object') {
                    for (k in value) {
                        if (Object.prototype.hasOwnProperty.call(value, k)) {
                            v = walk(value, k);
                            if (v !== undefined) {
                                value[k] = v;
                            } else {
                                delete value[k];
                            }
                        }
                    }
                }
                return reviver.call(holder, key, value);
            }


// Parsing happens in four stages. In the first stage, we replace certain
// Unicode characters with escape sequences. JavaScript handles many characters
// incorrectly, either silently deleting them, or treating them as line endings.

            text = String(text);
            cx.lastIndex = 0;
            if (cx.test(text)) {
                text = text.replace(cx, function (a) {
                    return '\\u' +
                        ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
                });
            }

// In the second stage, we run the text against regular expressions that look
// for non-JSON patterns. We are especially concerned with '()' and 'new'
// because they can cause invocation, and '=' because it can cause mutation.
// But just to be safe, we want to reject all unexpected forms.

// We split the second stage into 4 regexp operations in order to work around
// crippling inefficiencies in IE's and Safari's regexp engines. First we
// replace the JSON backslash pairs with '@' (a non-JSON character). Second, we
// replace all simple value tokens with ']' characters. Third, we delete all
// open brackets that follow a colon or comma or that begin the text. Finally,
// we look to see that the remaining characters are only whitespace or ']' or
// ',' or ':' or '{' or '}'. If that is so, then the text is safe for eval.

            if (/^[\],:{}\s]*$/
                .test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, '@')
                    .replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']')
                    .replace(/(?:^|:|,)(?:\s*\[)+/g, ''))) {

// In the third stage we use the eval function to compile the text into a
// JavaScript structure. The '{' operator is subject to a syntactic ambiguity
// in JavaScript: it can begin a block or an object literal. We wrap the text
// in parens to eliminate the ambiguity.

                j = eval('(' + text + ')');

// In the optional fourth stage, we recursively walk the new structure, passing
// each name/value pair to a reviver function for possible transformation.

                return typeof reviver === 'function'
                    ? walk({'': j}, '')
                    : j;
            }

// If the text is not JSON parseable, then a SyntaxError is thrown.

            throw new SyntaxError('JSON.parse');
        };
    }
}());
urlDispatcher.addRoutes({ 
    'module.airee': NETCAT_PATH + 'modules/airee/admin/?site_id=%1'
});
/*urlDispatcher.addRoutes( {
  // append tab
  'module.auth': NETCAT_PATH + 'modules/auth/admin.php',
  'module.auth.info': NETCAT_PATH + 'modules/auth/admin.php?phase=1',
  'module.auth.reg': NETCAT_PATH + 'modules/auth/admin.php?phase=2',
  'module.auth.reg.classic': NETCAT_PATH + 'modules/auth/admin.php?phase=2',
  'module.auth.reg.ex': NETCAT_PATH + 'modules/auth/admin.php?phase=3',
  'module.auth.mail': NETCAT_PATH + 'modules/auth/admin.php?phase=4',
  'module.auth.template': NETCAT_PATH + 'modules/auth/admin.php?phase=5',
  'module.auth.settings': NETCAT_PATH + 'modules/auth/admin.php?phase=6'
} );
*/

urlDispatcher.addRoutes({
    'module.auth': NETCAT_PATH + 'modules/auth/admin.php?view=info'
})
.addPrefixRouter('module.auth.', function(path, params) {
    var url = NETCAT_PATH + "modules/auth/admin.php?view=" + path.substr(12);
    if (params) {
        url += "&id=" + params;
    }
    mainView.loadIframe(url);
});
// $Id: url_routes.js 6206 2012-02-10 10:12:34Z denis $

urlDispatcher.addRoutes( { 
 
    1: '' // dummy entry
} );
urlDispatcher.addRoutes({ 
    'module.captcha': NETCAT_PATH + 'modules/captcha/admin/?site_id=%1'
});
/* $Id: url_routes.js 4308 2011-03-02 14:32:11Z gaika $ */

urlDispatcher.addRoutes( { 
  // append tab
  'module.comments': NETCAT_PATH + 'modules/comments/admin.php',
  'module.comments.list': NETCAT_PATH + 'modules/comments/admin.php',
  'module.comments.template': NETCAT_PATH + 'modules/comments/admin.php?phase=2',
  'module.comments.subscribe': NETCAT_PATH + 'modules/comments/admin.php?phase=3',
  'module.comments.converter': NETCAT_PATH + 'modules/comments/admin.php?phase=4',
  'module.comments.optimize': NETCAT_PATH + 'modules/comments/admin.php?phase=8',
  'module.comments.settings': NETCAT_PATH + 'modules/comments/admin.php?phase=9',
  'module.comments.subscribes': NETCAT_PATH + 'modules/comments/admin.php?phase=10',
  'module.comments.edit': NETCAT_PATH + 'modules/comments/admin.php?phase=15&comment=$1'

} );
urlDispatcher.addRoutes( { 
 
   1: '' // dummy entry
} );
// $Id: url_routes.js 6207 2012-02-10 10:14:50Z denis $

urlDispatcher.addRoutes( { 
    'module.filemanager': NETCAT_PATH + 'modules/filemanager/admin.php'
} );
urlDispatcher.addRoutes({
    'module.landing': NETCAT_PATH + 'modules/landing/admin/?controller=constructor&action=index',
      
    1: '' // dummy entry
});
// $Id: url_routes.js 6207 2012-02-10 10:14:50Z denis $

urlDispatcher.addRoutes( { 
    'module.logging': NETCAT_PATH + 'modules/logging/admin.php'
} );
urlDispatcher.addRoutes({
    "module.messaging": NETCAT_PATH + "modules/messaging/admin/?controller=settings&action=%1",
    "module.messaging.settings": NETCAT_PATH + "modules/messaging/admin/?controller=settings",
    "module.messaging.service": NETCAT_PATH + "modules/messaging/admin/?controller=service&action=%1",
    "module.messaging.service.edit": NETCAT_PATH + "modules/messaging/admin/?controller=service&action=edit&id=%1",
    "module.messaging.log": NETCAT_PATH + "modules/messaging/admin/?controller=log&action=%1",
    "module.messaging.send": NETCAT_PATH + "modules/messaging/admin/?controller=send&action=%1",

    1: ""
});

urlDispatcher.addRoutes( { 
 
    1: '' // dummy entry
} );
urlDispatcher.addRoutes({
    'module.requests': NETCAT_PATH + 'modules/requests/admin/?controller=settings&action=index',
    'module.requests.settings': NETCAT_PATH + 'modules/requests/admin/?controller=settings&action=index',
    'module.requests.list': NETCAT_PATH + 'modules/requests/admin/?controller=list&action=index&site_id=%1',
      
    1: '' // dummy entry
});
urlDispatcher.addRoutes({
    'module.search': NETCAT_PATH + 'modules/search/admin.php?view=info'
})
// лень прописывать 100500 адресов
.addPrefixRouter('module.search.', function(path, params) {
    var url = NETCAT_PATH + "modules/search/admin.php?view=" + path.substr(14);
    if (params) {
        url += "&id=" + params;
    }
    mainView.loadIframe(url);
});

urlDispatcher.addRoutes({
    'module.stats': NETCAT_PATH + 'modules/stats/openstat/admin.php',
    'module.stats.settings': NETCAT_PATH + 'modules/stats/settings.php',
    'module.stats.nc_stat': NETCAT_PATH + 'modules/stats/admin.php',
    'module.stats.openstat': NETCAT_PATH + 'modules/stats/openstat/admin.php',
    'module.stats.analytics': NETCAT_PATH + 'modules/stats/admin/?controller=analytics&action=index'
})
.addPrefixRouter('module.stats.openstat.', function(sub_view, params) {
    var url = NETCAT_PATH + "modules/stats/openstat/admin.php?sub_view=" + sub_view.substr('module.stats.openstat'.length+1);
    if (params) {
        url += "&phase=" + params;
    }
    mainView.loadIframe(url);
});

urlDispatcher.addRoutes( {
    // append tab
    'module.subscriber': NETCAT_PATH + 'modules/subscriber/admin.php',
    'module.subscriber.mailer': NETCAT_PATH + 'modules/subscriber/admin.php?phase=1',
    'module.subscriber.mailer.add': NETCAT_PATH + 'modules/subscriber/admin.php?phase=2',
    'module.subscriber.mailer.edit': NETCAT_PATH + 'modules/subscriber/admin.php?phase=2&mailer_id=%1',

    'module.subscriber.mailer.users': NETCAT_PATH + 'modules/subscriber/admin.php?phase=6&mailer_id=%1',

    'module.subscriber.stats': NETCAT_PATH + 'modules/subscriber/admin.php?phase=3',
    'module.subscriber.stats.mailer': NETCAT_PATH + 'modules/subscriber/admin.php?phase=4&mailer_id=%1',

    'module.subscriber.settings': NETCAT_PATH + 'modules/subscriber/admin.php?phase=5',

    'module.subscriber.once': NETCAT_PATH + 'modules/subscriber/admin.php?phase=7'

} );
urlDispatcher.addRoutes( { 
 
    1: '' // dummy entry
} );
/* $Id: main_view.js 8377 2012-11-08 14:18:24Z lemonade $ */

mainView = {
    TopTitle: {},
    chan: 0,  // 1 - есть изменения, 0 - нету
    remindSaveTrigger: false,
    to_tab_id: 0, // таб, на которой надо переключиться
    count: 0,
    showMainView: function() {},
    showStartScreen: function() {},
    //ckeditors_state: Array(),  // состояния визуальных редакторов

    setHeader: function(headerText, subheaderText) {
        headerText = headerText || '';
        subheaderText = subheaderText || '';
        if (subheaderText) {
            subheaderText = ' <small>' + subheaderText.toString() + '</small>';
        }
        var header = headerText + subheaderText;
        document.getElementById('mainViewHeader').innerHTML = header ? "<h1>" + header + "</h1>" : '';

        this.TopTitle["headerText"] = headerText.toString().replace(/<.*?>/ig, '');
    },

    activeTab: null,

    addTab: function(name, caption, action, isActive, unactive, tab_settings) {
        if (caption === ncLang.NetcatCMS) {
            $nc('div.slider_block').hide();
        } else {
            $nc('div.slider_block').show();
        }
        var tabContainer = $nc('#mainViewTabs');
        var tabContainerTray = $nc('#mainViewTabsTray');

        var icon = 'sprite' in tab_settings ? '<i class="nc-icon nc--' + tab_settings.sprite + '"></i>' : '';
        var tab = $nc('<li id="mainViewTab_' + name + '"><span>' + icon + caption + '</span></li>');

        //var spacer = document.createElement('DIV');
        //spacer.className = 'between_tabs';

        //var tab = document.createElement('DIV');
        //tab.id = 'mainViewTab_'+name;
        //var liclass = '';

        if (isActive) {
            //$nc('#' + tab.id + ' li').addClass('sel');
            this.activeTab = tab.attr('id');
            this.TopTitle["activeTab"] = caption;
            this.updateTopTitle();
            tab.addClass('sel');
            //var liclass = 'sel';
        }

        //tab.innerHTML = "<li class='"+liclass+"'>" + caption + "</li>";
        if (!unactive) {
            tab.click(function() {
                mainView.switchTab(this.id);
            });
        }
        tab.get(0).action = action;

        //var tabContainer = document.getElementById('mainViewTabs');

        if ('pull_right' in tab_settings) {
            tabContainerTray.append(tab);
        } else {
            tabContainer.append(tab);
        }

        //.append('<div class="between_tabs"></div>')
        //tabContainer.appendChild(tab);
        //tabContainer.appendChild(spacer);
    },

    removeAllTabs: function() {
        nc('#mainViewTabs').html('');
        nc('#mainViewTabsTray').html('');
        nc('#mainViewToolbar').html('');
        this.activeTab = null;
        this.TopTitle["activeTab"] = "";
    },

    switchTab: function(tabId, dontEvalAction, dontCheck) {
        if (tabId == 'mainViewTab_view') {
            var el = document.getElementById(tabId);
            if (el && el.action) {
                eval(el.action);
            }
            return true;
        }

        to_tab_id = tabId;
        if (!dontCheck && this.chan && REMIND_SAVE == '1' && this.remindSaveTrigger) {
            this.checkRemindSave();
            return true;
        }

        if (this.activeTab) {
            if (this.activeTab == tabId) {
                if (!dontEvalAction) { //also: call from "onlick",  but no from "update"
                    urlDispatcher.process(window.location.hash);
                }
                return true;
            }
            var oActiveTab = document.getElementById(this.activeTab);
            $nc('li#' + oActiveTab.id + '').removeClass('sel');
        }
        this.displayStar(0);
        this.chan = 0;

        if (!document.getElementById(tabId)) {
            return;
        }

        if (null != document.getElementById(this.activeTab)) {
            document.getElementById(this.activeTab).title = '';
        }

        document.getElementById(tabId).title = TEXT_REFRESH;
        var oTab = document.getElementById(tabId);
        if (!oTab) return false;
        if (!dontEvalAction && oTab.action) {
            eval(oTab.action);
        }

        $nc('#' + tabId).addClass('sel');

        this.activeTab = tabId;
        this.TopTitle["activeTab"] = oTab.firstChild.textContent;
        this.updateTopTitle();
    },

    toolbar: null, // to be created during initialization on window load
    init: function() {
        this.toolbar = new toolbar('mainViewToolbar');
        this.oStartScreen = document.getElementById('startScreen');
        this.oMainView = document.getElementById('mainView');
        this.oIframe = document.getElementById('mainViewIframe');
        this.TopTitle["Netcat"] = ncLang.SystemName;
    },

    loadIframe: function(url) {
        // FF restores frame locations on reload -- prevent it
        this.showMainView();
        var fullURL = window.location.protocol + '//' + window.location.host + url;
        if (this.oIframe) {
            this.oIframe.contentWindow.location.replace(url);
        }

        nc.process_start('loadIframe()', this.oIframe);
        $nc(this.oIframe).load(function() {
            nc.process_stop('loadIframe()', this.oIframe);
        });
    },

    refreshIframe: function() {
        var loc = this.oIframe.contentWindow.location.href,
            newLoc = loc.replace(/([&?]rand=)[^&]+/, "$1" + Math.random());

        if (loc == newLoc) {
            newLoc += (loc.match(/\?/) ? '&' : '?') + "rand=" + Math.random();
        }

        this.oIframe.contentWindow.location.href = newLoc;
    },

    submitIframeForm: function(formId) {
        var oForm;

        if (formId) {
            oForm = document.getElementById('mainViewIframe').contentWindow.document.getElementById(formId);
        }

        if (!oForm) {
            oForm = document.getElementById('mainViewIframe').contentWindow.document.forms.item(0);
        }

        var iframeCkeditor = document.getElementById('mainViewIframe').contentWindow.CKEDITOR;

        if (iframeCkeditor != 'undefined' && iframeCkeditor && iframeCkeditor.hasOwnProperty('instances')) {
            for (var instance_name in iframeCkeditor.instances) {
                iframeCkeditor.instances[instance_name].updateElement();
            }
        }

        // don’t submit if there’ an onsubmit handler and it returns false
        if (typeof oForm.onsubmit == "function" && oForm.onsubmit() === false) {
            return;
        }

        oForm.submit();
    },

    submitIframeFormWithJson: function() {
        var oForm;

        oForm = document.getElementById('mainViewIframe').contentWindow.document.forms.item(0);

        var $oForm = $nc(oForm);

        if ($oForm.find('INPUT[name=collect_post]').length) {
            var $input = $nc('<input type="hidden" name="collected_post"/>');
            $input.val($oForm.serialize());
            $input.prependTo($oForm);

            $oForm.find('INPUT,SELECT').not('[name=collected_post]').attr('name', '');
        }

        this.submitIframeForm();
    },

    resetIframeForm: function() {
        document.getElementById('mainViewIframe').contentWindow.document.forms.item(0).reset();
    },

    /* { headerText
     *   tabs: { id
     *           caption
     *           location
     *           unactive
     *         }
     *   toolbar: [ { id
     *                caption
     *                action | location
     *                group
     *               }
     *            ]
     *   actionButtons:  [ { id
     *                       caption
     *                       action | location
     *                       align
     *                     }
     *                   ]
     *
     *   activeTab
     *   activeToolbarButtons: [ btnname1, btnname2 ]
     *
     *   locationHash
     *
     *   tabsCrc
     *   toolbarCrc
     *   actionButtonsCrc
     *
     *   treeMode
     *   treeSelectedNode
     *
     *   treeChanges { 'addNode': [ <nodeData>+ ],
     *                 'updateNode': [ <nodeData>+ ],
     *                 'deleteNode': [ nodeId+ ],
     *                 'moveNode': [ nodeId, position, refNodeId ]
     *     // both addNode and updateNode call tree.addNode()
     *
     */
    currentSettings: {
        headerText: null,
        tabs: null,
        toolbar: null,
        actionButtions: null,
        activeTab: null,
        locationHash: null,
        tabsCrc: null,
        toolbarCrc: null,
        actionButtonsCrc: null
    },

    updateSettings: function(newSettings) {
        if (!newSettings) return false;

        this.chan = 0;
        this.remindSaveTrigger = false;

        var currentSettings = this.currentSettings; // 'for short'

        this.setHeader(newSettings.headerText, newSettings.subheaderText);

        // hash has changed
        if (newSettings.locationHash) {
            urlDispatcher.updateHash(newSettings.locationHash);
        }
        // of hash has changed


        if (newSettings.tabsCrc != currentSettings.tabsCrc) {

            this.removeAllTabs();
            for (var i = 0; i < newSettings.tabs.length; i++) {
                var action = "";
                if (newSettings.tabs[i].location && !newSettings.tabs[i].unactive) {
                    action = 'urlDispatcher.load("' + newSettings.tabs[i].location + '")';
                }
                if (newSettings.tabs[i].action) {
                    action = newSettings.tabs[i].action;
                }
                var isActive = (newSettings.activeTab == newSettings.tabs[i].id);
                this.addTab(newSettings.tabs[i].id, newSettings.tabs[i].caption, action, isActive, newSettings.tabs[i].unactive, newSettings.tabs[i]);
            }

            if (null != document.getElementById('mainViewTab_' + newSettings.activeTab)) {
                document.getElementById('mainViewTab_' + newSettings.activeTab).title = TEXT_REFRESH;
            }
        } else {
            this.switchTab('mainViewTab_' + newSettings.activeTab, true);
        }

        if (typeof newSettings.tabs.length == 'undefined' || newSettings.tabs.length < 2) {
            $nc('#tabs').hide();
            this.TopTitle["headerText"] = ('' + (newSettings.headerText || '') + (newSettings.subheaderText ? ' / ' + newSettings.subheaderText : '')).replace(/<.*?>/ig, '');

            this.updateTopTitle();
        }

        // toolbar has been changed

        if (!newSettings.toolbar.length) {
            try {
                this.toolbar.makeEmptyToolbar();
            } catch (e) {}
            $nc('#sub_tabs').hide();
        } else if (this.toolbar) {
            this.toolbar.clear();
            $nc('#sub_tabs').css("display", "block");
            for (var i = 0; i < newSettings.toolbar.length; i++) {
                var btn = newSettings.toolbar[i];
                if (btn.location) btn.action = 'urlDispatcher.load("' + btn.location + '")';
                this.toolbar.addButton(btn);
            }
        }

        // tabs changed

        if (newSettings.activeToolbarButtons && newSettings.activeToolbarButtons.length && this.toolbar) {
            for (var i = 0; i < newSettings.activeToolbarButtons.length; i++) {
                var oBtn = document.getElementById(this.toolbar.toolbarId + '_' + newSettings.activeToolbarButtons[i]);
                if (oBtn) {
                    oBtn.turnOn();
                    this.TopTitle["activeButton"] = oBtn.textContent;
                    this.updateTopTitle();
                }
            }
        }

        // ActionButtons have been changed
        if (newSettings.actionButtonsCrc != currentSettings.actionButtonsCrc) {
            var html = '';
            for (var i = 0; i < newSettings.actionButtons.length; i++) {
                var btn = newSettings.actionButtons[i];
                var style = 'save';
                if (null != btn.style) {
                    style += ' ' + btn.style;
                }
                var align = (btn.align == 'left') ? 'left' : 'right';

                if (btn.location) {
                    btn.action = "urlDispatcher.load('" + btn.location + "')";
                }

                var border = typeof(btn.red_border) != 'undefined' && btn.red_border ? 'border: 2px solid red;' : '';

                html += '<div class=\"' + style + '\" style=\"' + border + 'float: ' + align + ';\" onclick=\"' + btn.action + '\" title = \"' + btn.caption + '\">' + btn.caption + '</div>';
            }
            var mvb = $nc('#mainViewButtons');
            mvb.html(html);
            if ($nc('div', mvb).length == 0) {
                mvb.parent().hide();
                $nc('.clear_footer').hide();
                $nc('.middle_border').css({top: '0px'});
            } else {
                // В Firefox элемент с `display: none;` в CSS иногда не отображается через метод `jQuery.show();`
                mvb.parent().css('display', 'block');
                $nc('.clear_footer').show();
                //$nc('.middle_border').css({top:'-45px'});
            }
        }

        // changes in the tree: addNode, updateNode, deleteNode
        // ANY OTHER tree METHODS CAN BE ADDED TO newSettings WITHOUT CHANGES
        // TO THIS CODE
        // drawback: must be changed in case tree methods are refactored!
        var tree;
        if (newSettings.treeChanges && (tree = document.getElementById('treeIframe').contentWindow.tree)) {
            for (var method in newSettings.treeChanges) {
                if (typeof tree[method] == 'function' && newSettings.treeChanges[method].length) {
                    for (var i = 0; i < newSettings.treeChanges[method].length; i++) {
                        // call method in the tree
                        tree[method](newSettings.treeChanges[method][i]);
                    }
                }
            }
        } // of if "there are tree changes and tree exists"

        // treeMode determines type of content in the tree
        if (newSettings.treeMode) {
            treeSelector.changeMode(newSettings.treeMode, newSettings.treeSelectedNode);
        }

        if (newSettings.addNavBarCatalogue) {
            var $navBar = $nc('BODY.nc-admin .nc-navbar');
            if ($navBar.length != 0) {
                var $newSite = $nc('<li><a href="' + newSettings.addNavBarCatalogue.href + '"><i class="nc-icon nc--site"></i> ' + newSettings.addNavBarCatalogue.name + '</a></li>');
                var $dropdown = $navBar.find('LI.nc--dropdown').eq(0);
                var $divider = $dropdown.find('LI.nc-divider');
                if ($divider.length) {
                    $divider.before($newSite);
                } else {
                    var $ul = $dropdown.find('UL');
                    $ul.prepend($nc('<li class="nc-divider"></li>'));
                    $ul.prepend($newSite);
                }
            }
        }

        if (newSettings.deleteNavBarCatalogue) {
            var $navBar = $nc('BODY.nc-admin .nc-navbar');
            if ($navBar.length != 0) {
                var $dropdown = $navBar.find('LI.nc--dropdown').eq(0);
                for (var index in newSettings.deleteNavBarCatalogue) {
                    var id = newSettings.deleteNavBarCatalogue[index];
                    $dropdown.find('A').each(function() {
                        if ($nc(this).attr('href') == '#site.map(' + id + ')') {
                            $nc(this).closest('LI').remove();
                            return false;
                        }
                        return true;
                    });
                }

                var $divider = $dropdown.find('LI.nc-divider');
                if ($divider.prev('LI').length == 0) {
                    $divider.remove();
                }
            }
        }

        // SAVE SETTINGS
        this.currentSettings = newSettings;


        if (!document.all) { // reflow bugs in mozilla & opera
            triggerResize();
        }
    },

    updateTopTitle: function() {
        var title = "";
        var i, value, value_bak;
        for (i in this.TopTitle) {
            value = this.TopTitle[i];
            if ((value_bak && value_bak == value) || value == "" || value == undefined) continue;
            title += "" + (value_bak ? " / " : "") + value + "";
            value_bak = value;
        }
        this.TopTitle["activeButton"] = "";
        top.window.document.title = title;
    }, // updateTopTitle

    displayStar: function(visible) { //Показать звездочку. 1 - есть, 0 - нету.
        var $activeTab = $nc('#' + this.activeTab);
        if ($activeTab.length) {
            $activeTab.find('.star').remove();
            if (visible) {
                var $star = $nc('<span class="star"> *</span>').css('color', 'red');
                $star.appendTo($activeTab);
            }
        }
    }, //displayStar

    checkRemindSave: function() {
        if (this.chan) {
            if (confirm(ncLang.RemindSaveWarning)) {
                mainView.rsExit()
            }
        } else {
            this.switchTab(to_tab_id, 0, 1);
        }
    },

    rsExit: function() {
        this.displayStar(0);
        this.switchTab(to_tab_id, 0, 1);
    },


    rsCancel: function() {
        document.getElementById('remindSave').style.display = 'none';
    },


    rsSave: function() {
        var oIframe = top.frames['mainViewIframe'];
        var docum = (oIframe.contentWindow || oIframe.contentDocument || oIframe.document);

        oForm = docum.forms.item(0) || docum.document.forms.item(0);

        if (typeof oIframe.formAsyncSave == 'function') {
            oIframe.formAsyncSave(oForm, {
                '*': 'top.mainView.rsStatus(this.xhr)'
            });
        } else if (typeof docum.formAsyncSave == 'function') {
            docum.formAsyncSave(oForm, {
                '*': 'top.mainView.rsStatus(this.xhr)'
            });
        } else {
            this.rsExit();
        }

        document.getElementById('remindSave').style.display = 'none';
    },

    rsStatus: function(xhr) {
        if (xhr.status != "200" || xhr.readyState == 4) {
            this.rsExit();
        }
    },

    attachClickHandler: function(selector, handler) {
        $nc(selector).attr('onclick', '').unbind('click').bind('click', handler);
    }
}; // of MAINVIEW

/** INIT **/
bindEvent(window, 'load', function() {
    mainView.init()
});

