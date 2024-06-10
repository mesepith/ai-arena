var monitorScrolling = false;
var aiTyping = false;

function monitorUserScroll() {
    if (monitorScrolling) {
        var scrollPosition = $('#chatBox').scrollTop() + $('#chatBox').innerHeight();
        var nearBottom = scrollPosition >= $('#chatBox')[0].scrollHeight - 100;

        if (nearBottom) {
            $('#chatBox').scrollTop($('#chatBox').prop("scrollHeight"));
        }

        setTimeout(monitorUserScroll, 300);
    }
}

$('#chatBox').on('scroll', function() {
    var currentScrollPos = $(this).scrollTop();
    if (currentScrollPos < prevScrollPos) {
        if (aiTyping) {
            monitorScrolling = false;
        }
    } else {
        if (aiTyping) {
            monitorScrolling = true;
            monitorUserScroll();
        }
    }
    prevScrollPos = currentScrollPos;
});

function updateScrollBehavior(engage) {
    monitorScrolling = engage;
    if (engage) {
        monitorUserScroll();
    }
}
