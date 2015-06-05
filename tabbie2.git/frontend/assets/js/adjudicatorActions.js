function init() {
    th = $("#debateDraw-container thead th:last-child");
    th.html(th.html() + " <i></i>");

    /**
     * Function when clicked on a Name in the round/view
     */
    $(".adj").on("click.target.popoverX", function () {
        href = $(this).data("href");
        root = document.querySelector($(this).data("target"));
        contentdiv = $(root).find(".popover-content");
        if (this.classList.contains('toLoad')) {
            this.classList.remove('toLoad');
            $.ajax({
                type: "GET",
                url: href
            }).success(function (data) {
                $(contentdiv).html(data);
                root.style.top = (parseInt(root.style.top)) + "px";
            }).error(function (jqXHR, textStatus, errorThrown) {
                console.error(textStatus + " : " + errorThrown);
            });
        }
    });

    /**
     * Drag and Drop Function
     */
    $(".adj_panel li").on("dragstart", function (e) {
        if ($(this.parentNode).find('li').length > 1) {
            window.dragid = $(this.children[1]).data("id");
            window.panelid = $(this.parentNode).data("panel");
        }
        else {
            addFlash('info', 'There has to be at least 1 adjudicatur per room');
            e.preventDefault();
        }
    });

    $(".adj_panel").on("drop", function (event) {
        //console.log(event);
        href = $("#debateDraw").data("href");
        panel = $(this).data("panel");
        placeholder = $(this).find("li.sortable-placeholder");
        position = $(this).find("li").index(placeholder);
        console.log("#", window.dragid, "from Panel", window.panelid, "to", panel, "at pos", position);

        th = $("#debateDraw-container thead th:last-child i");
        th[0].className = "glyphicon glyphicon-refresh";

        $.ajax({
            type: "POST",
            url: href,
            data: {
                id: window.dragid,
                old_panel: window.panelid,
                new_panel: panel,
                pos: position
            }
        }).success(function (data) {
            if (data == "1") {
                //console.log("Saved!");
                th = $("#debateDraw-container thead th:last-child i");
                th[0].className = "glyphicon glyphicon-ok-circle text-success";
            }
            else {
                th = $("#debateDraw-container thead th:last-child i");
                th[0].className = "glyphicon glyphicon-warning-sign text-warning";
                console.log("Return ERROR data", data);
            }
        }).error(function (jqXHR, textStatus, errorThrown) {
            th = $("#debateDraw-container thead th:last-child i");
            th[0].className = "glyphicon glyphicon-remove-circle text-danger";
            console.error(textStatus + " : " + errorThrown);
            console.error(jqXHR.responseText);
        });
    });
}
$("#debateDraw-pjax").on("pjax:end", function () {
    init();
});

$(document).ready(function () {
    init();
});