var TinyLXPPlatformText = '';
var TinyLXPPlatformProps = null;
var currentSectionState = "create";
var currentsectionId = 0;

function tinyLxpCopyText(value, callback) {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(value).then(function () {
      callback(true);
    }).catch(function () {
      callback(false);
    });
    return;
  }

  var temp = document.createElement('textarea');
  temp.value = value;
  temp.setAttribute('readonly', 'readonly');
  temp.style.position = 'absolute';
  temp.style.left = '-9999px';
  document.body.appendChild(temp);
  temp.select();
  var ok = false;
  try {
    ok = document.execCommand('copy');
  } catch (e) {
    ok = false;
  }
  document.body.removeChild(temp);
  callback(ok);
}

function tinyLxpSetCurrikiStatus(text, isError) {
  var statusNode = document.getElementById('currikistudio-copy-status');
  if (!statusNode) {
    return;
  }
  statusNode.textContent = text;
  statusNode.className = isError ? 'currikistudio-copy-status-error' : 'currikistudio-copy-status-ok';
}

function tinyLxpRenderCurrikiPreview(payload) {
  var title = payload && payload.title ? payload.title : '';
  var shortcode = payload && payload.shortcode ? payload.shortcode : '';

  var titleNode = document.getElementById('currikistudio-selected-title');
  var shortcodeNode = document.getElementById('currikistudio-shortcode-preview');
  if (titleNode) {
    titleNode.textContent = title;
  }
  if (shortcodeNode) {
    shortcodeNode.value = shortcode;
  }
  tinyLxpSetCurrikiStatus('', false);
}

window.tinyLxpHandleCurrikiSelection = tinyLxpRenderCurrikiPreview;

(function (wp) {
  var TinyLXPPlatformIcon = wp.element.createElement(wp.primitives.SVG, {
    xmlns: "http://www.w3.org/2000/svg",
    viewBox: "0 0 24 24"
  }, wp.element.createElement(wp.primitives.Path, {
    d: "M6 14H4V6h2V4H2v12h4M7.1 17h2.1l3.7-14h-2.1M14 4v2h2v8h-2v2h4V4"
  }));

  var TinyLXPPlatformButton = function (props) {
    return wp.element.createElement(
      wp.blockEditor.RichTextToolbarButton, {
      icon: TinyLXPPlatformIcon,
      title: 'Tiny LXP tool',
      onClick: function () {
        if (typeof props.value.start === 'undefined') {
          props.value.start = props.value.text.length;
          props.value.end = props.value.text.length;
        }
        TinyLXPPlatformText = '';
        if (props.value.end > props.value.start) {
          TinyLXPPlatformText = props.value.text.substr(props.value.start, props.value.end - props.value.start);
        }
        TinyLXPPlatformProps = props;
        jQuery('.tiny-lxp-platform-modal').addClass("active");
      },
    }
    );
  }
  wp.richText.registerFormatType(
    'lti-platform-format/insert-tool', {
    title: 'Tiny LXP tool',
    tagName: 'ltiplatformtool',
    className: null,
    edit: TinyLXPPlatformButton,
  }
  );
})(window.wp);

(function ($) {
  $(document).ready(function () {
    var totalChips = $("#option-chips").attr('tota-chips');

    var existingUrl = $('#lti_tool_url').val();
    var existingTitle = $('#lti_content_title').val();
    if (existingUrl) {
      tinyLxpRenderCurrikiPreview({
        title: existingTitle || '',
        shortcode: '[currikistudio url=' + existingUrl + ']'
      });
    }

    $('body').on('click', '#currikistudio-copy-title', function () {
      var title = $('#currikistudio-selected-title').text();
      if (!title) {
        tinyLxpSetCurrikiStatus('No title to copy yet.', true);
        return;
      }
      tinyLxpCopyText(title, function (ok) {
        tinyLxpSetCurrikiStatus(ok ? 'Title copied.' : 'Unable to copy title.', !ok);
      });
    });

    $('body').on('click', '#currikistudio-copy-shortcode', function () {
      var shortcode = $('#currikistudio-shortcode-preview').val();
      if (!shortcode) {
        tinyLxpSetCurrikiStatus('No shortcode to copy yet.', true);
        return;
      }
      tinyLxpCopyText(shortcode, function (ok) {
        tinyLxpSetCurrikiStatus(ok ? 'Shortcode copied.' : 'Unable to copy shortcode.', !ok);
      });
    });

    function deeplink() {
      var urlParams = new URLSearchParams(window.location.search);
      window.open('../?lti-platform&deeplink&post=' + encodeURIComponent(urlParams.get('post')) + '&tool=' + encodeURIComponent($("input[name='tool']:checked").val()), '_blank', 'width=1000,height=800');
      $('.tiny-lxp-platform-modal').removeClass('active');
    }

    $.get('../?lti-platform&tools', function (response) {
      $('#wpwrap').append(response);
      $('.tiny-lxp-platform-tool').on('change', function () {
        $('#tiny-lxp-platform-select').prop('disabled', false);
      });

      $('#tiny-lxp-platform-select').on('click', function () {
        $.get('../?lti-platform&usecontentitem&tool=' + encodeURIComponent($("input[name='tool']:checked").val()), function (response) {
          if (response.useContentItem) {
            deeplink();
          } else {
            if (!window.TinyLXPPlatformText) {
              window.TinyLXPPlatformText = $('input[name="tool"]:checked').attr('toolname');
            }
            if ($("#preview_lit_connections").length) {
              deeplink();
            }
            var id = Math.random().toString(16).substr(2, 8);
            window.TinyLXPPlatformProps.onChange(window.wp.richText.insert(window.TinyLXPPlatformProps.value, '[lti-platform tool=' + $("input[name='tool']:checked").val() + ' id=' + id + ']' + window.TinyLXPPlatformText + '[/lti-platform]'));
            window.TinyLXPPlatformProps.onFocus();
            $('.tiny-lxp-platform-modal').removeClass('active');
          }
        });
      });

      $('#tiny-lxp-platform-cancel').on('click', function () {
        $('.tiny-lxp-platform-modal').removeClass('active');
        $('#postdivrich').addClass('wp-editor-expand');
      });

      $('#preview_lit_connections').on('click', function () {
        jQuery('.tiny-lxp-platform-modal').addClass("active");
        $('#postdivrich').removeClass('wp-editor-expand');
      });

      $(".tool-input-tr").on('click', function () {
        $(this).find('td input[type=radio]').prop('checked', true);
        $('#tiny-lxp-platform-select').prop("disabled", false);
      });

      $('body').on('click', '.chip-close', function () {
        if (confirm("Are you sure you want to remove?") == true) {
          var sectionId = $(this).parent('div').attr('identifier');
          var host = window.location.origin + '/wp-json/lms/v1/delete/trek/section';
          jQuery.ajax({
            type: "post",
            dataType: "json",
            url: host,
            data: { section_id: sectionId },
            success: function (response) {
              if (window.currentSectionState == "edit" && window.currentsectionId == sectionId) {
                var url = window.location.href;
                if (url.indexOf("&action=edit") >= 0) {
                  $('#playlist-select-area').css("display", "inline");
                }
                $('#section-title').text("Add New Section");
                $('#btnSaveSection').text("Create");
                CKEDITOR.instances['ck-editor-id'].setData('');
                $('#option-title-select-box').val('');
                window.currentSectionState = "create";
                window.currentsectionId = 0;
              }
              $("[identifier=" + sectionId + "]").remove();
              appendCoursePlaylistSelectOptions();
            }
          });
        }
      });

      appendCoursePlaylistSelectOptions();
      function appendCoursePlaylistSelectOptions(selctedOption = null) {
        var options = '';
        var courseId = $('#course_select_options').find(":selected").val();
        var postID = $('#post_ID').val();
        var host = window.location.origin + '/wp-json/lms/v1/get/playlists';
        jQuery.ajax({
          type: "get",
          dataType: "json",
          url: host,
          data: { course_id: courseId, post_id: postID },
          success: function (response) {
            if (response.length == 0 && selctedOption == null) {
              options += '<option> No Section Available </option>';
            } else {
              options += '<option>---Select Section---</option>';
            }
            for (var j = 0, len = response.length; j < len; ++j) {
              options += '<option value="' + response[j] + '">' + response[j] + '</option>';
            }
            if (selctedOption != null) {
              options += selctedOption;
            }

            $("#option-title-select-box").html(options);
          }
        });
      }

      $('body').on('click', '#btnSaveStudentSection', function () {
        var content = CKEDITOR.instances['student-section-editor'].getData();
        var postID = $('#post_ID').val();
        // post content to server
        jQuery.ajax({
          type: "post",
          dataType: "json",
          url: window.location.origin + ajaxurl,
          data: { action: 'trek_student_section', content: content, post_id: postID },
          success: function (response) {
            console.log('response >>>>>>>>>> ', response);
            if (response.status == 200) {
              alert("Section saved successfully");
            }
          }
        });
      });

      $('body').on('click', '#btnSaveSection', function () {
        
        var title = $('#option-title-select-box').val();

        if (title.indexOf("No Section Available") >= 0 || title.indexOf("---Select Section---") >= 0) {
          alert("No section selected");
          return;
        }
        var content = CKEDITOR.instances['ck-editor-id'].getData();
        var sort = $('#trek_sort').val();
        var postID = $('#post_ID').val();
        var host = window.location.origin + '/wp-json/lms/v1/store/trek/section';
        $("[identifier=" + window.currentsectionId + "]").find('.edit-trek-options').removeClass("active-edit-trek-option");
        $("[identifier=" + window.currentsectionId + "]").find('.chip-close').removeClass("active-chip-close");
        $("[identifier=" + window.currentsectionId + "]").removeClass("edit-playlist-chip");
        $("[identifier=" + window.currentsectionId + "]").find('.edit-trek-options').css("visibility", "visible");
        $("[identifier=" + window.currentsectionId + "]").find('.chip-close').css("visibility", "visible");
        jQuery.ajax({
          type: "post",
          dataType: "json",
          url: host,
          data: { title: title, content: content, post_id: postID, section_id: window.currentsectionId, sort },
          success: function (recordId) {
            if (recordId == 0) {
              alert('Please enter post "Title" and "Description" first.');
            } else {
              appendCoursePlaylistSelectOptions();
              if (window.currentSectionState == "edit") {
                $('#chip-title-' + window.currentsectionId).text();
              } else {
                $("#option-chips").append('<div class="playlist-chip" identifier="' + recordId + '">  <span id="chip-title-' + recordId + '"> ' + title + ' </span>  <span class="edit-trek-options"><span style="margin-top:5px" class="dashicons dashicons-edit"></span> </span> <span type="button" class="chip-close"><span style="margin-top:5px" class="dashicons dashicons-no"></span> </span> </div>');
              }
              $('#playlist-select-area').css("display", "inline");
              window.currentsectionId = 0;
              window.currentSectionState = "create";
              CKEDITOR.instances['ck-editor-id'].setData('');
              $('#option-title-select-box').val('');
              $('#section-title').text("Add New Section");
              $('#btnSaveSection').text("Create");
              $('#chips-alternate').text("");
              $('#btnCancelUpdate').css("display", "none");
              $('#trek_sort').val(0);
              location.reload();
            }
          }
        });
      });
      $('body').on('click', '#btnCancelUpdate', function () {
        appendCoursePlaylistSelectOptions();
        $("[identifier=" + window.currentsectionId + "]").find('.edit-trek-options').removeClass("active-edit-trek-option");
        $("[identifier=" + window.currentsectionId + "]").find('.chip-close').removeClass("active-chip-close");
        $("[identifier=" + window.currentsectionId + "]").removeClass("edit-playlist-chip");
        $("[identifier=" + window.currentsectionId + "]").find('.edit-trek-options').css("visibility", "visible");
        $("[identifier=" + window.currentsectionId + "]").find('.chip-close').css("visibility", "visible");
        window.currentsectionId = 0;
        window.currentSectionState = "create";
        CKEDITOR.instances['ck-editor-id'].setData('');
        $('#option-title-select-box').val('');
        $('#section-title').text("Add New Section");
        $('#btnSaveSection').text("Create");
        $('#chips-alternate').text("");
        $('#btnCancelUpdate').css("display", "none");
        $('#playlist-select-area').css("display", "inline");
        $('#trek_sort').val(0);

      });
      $('body').on('click', '.edit-trek-options', function () {
        var url = window.location.href;
        $('#playlist-select-area').css("display", "none");
        window.currentSectionState = "edit";
        $('#btnSaveSection').text("Update");
        $('#btnCancelUpdate').css("display", "inline-block");
        $("[identifier=" + window.currentsectionId + "]").find('.edit-trek-options').removeClass("active-edit-trek-option");
        $("[identifier=" + window.currentsectionId + "]").find('.chip-close').removeClass("active-chip-close");
        $("[identifier=" + window.currentsectionId + "]").removeClass("edit-playlist-chip");
        $("[identifier=" + window.currentsectionId + "]").find('.edit-trek-options').css("visibility", "visible");
        $("[identifier=" + window.currentsectionId + "]").find('.chip-close').css("visibility", "visible");
        var sectionId = $(this).parent('div').attr('identifier');
        // $("[identifier=" + sectionId + "]").find('.edit-trek-options').addClass("active-edit-trek-option");
        // $("[identifier=" + sectionId + "]").find('.chip-close').addClass("active-chip-close");
        $("[identifier=" + sectionId + "]").find('.edit-trek-options').css("visibility", "hidden");
        $("[identifier=" + sectionId + "]").find('.chip-close').css("visibility", "hidden");
        $("[identifier=" + sectionId + "]").addClass("edit-playlist-chip");
        window.currentsectionId = sectionId;
        var host = window.location.origin + '/wp-json/lms/v1/get/trek/section';
        jQuery.ajax({
          type: "get",
          dataType: "json",
          url: host,
          data: { section_id: sectionId },
          success: function (response) {
            $('#section-title').text("Edit \"" + response[0].title + "\" Section");
            $('#trek_sort').val(parseInt(response[0].sort));
            CKEDITOR.instances['ck-editor-id'].setData(response[0].content);
            if ($("#option-title-select-box option[value='" + response[0].title + "']").length == 0) {
              option = '<option selected value="' + response[0].title + '">' + response[0].title + '</option>';
              appendCoursePlaylistSelectOptions(option);
            }
            if (response.length > 0) {
              $("#option-title-select-box").val(response[0].title.trim());
            }
          }
        });
      });

      $("#course_select_options").on('change', function () {
        appendCoursePlaylistSelectOptions();
      });


      $('body').on('click', '#school_remove_lxp_user', function () {
        if (confirm("Are you sure you want to remove?") == true) {
          var userId = $(this).attr('lxp_user_id');
          var host = window.location.origin + '/wp-json/lms/v1/delete/school/lxp/user';
          jQuery.ajax({
            type: "post",
            dataType: "json",
            url: host,
            data: { user_id: userId },
            success: function (response) {
            }
          });
          $(this).parent().fadeOut();
        }
      });
    });

    // -----------------------------------------------------------------------
    // AI Content Gen — Generate button
    // -----------------------------------------------------------------------
    $('body').on('click', '#lxp-ai-content-gen-btn', function () {
      var postId = $('#lxp-ai-gen-post-id').val();
      if (!postId) {
        return;
      }

      var lessonContent = tinyLxpGetEditorContent();

      if (!lessonContent.trim()) {
        tinyLxpSetAiStatus('Please add lesson content before generating.', true);
        return;
      }

      tinyLxpRunAiGeneration('/wp-json/lms/v1/lesson/ai-content', postId, lessonContent, 'Generating\u2026 this may take 15\u201330 seconds.', function (response) {
        var statusMsg = 'Content generated. Review and click \u201cUpdate\u201d to save.';
        if (response && response.template_id) {
          statusMsg += ' (Template ' + response.template_id + ' applied)';
        }
        return statusMsg;
      });
    });

    // -----------------------------------------------------------------------
    // AI Content Gen — Block mode button
    // -----------------------------------------------------------------------
    $('body').on('click', '#lxp-ai-blocks-gen-btn', function () {
      var postId = $('#lxp-ai-gen-post-id').val();
      if (!postId) {
        return;
      }

      var lessonContent = tinyLxpGetEditorContent();
      if (!lessonContent.trim()) {
        tinyLxpSetAiStatus('Please add lesson content before generating.', true);
        return;
      }

      tinyLxpRunAiGeneration('/wp-json/lms/v1/lesson/ai-content-blocks', postId, lessonContent, 'Rendering block-based lesson\u2026', function (response) {
        var count = response && response.blocks_rendered ? response.blocks_rendered : 0;
        return 'Block content generated. ' + count + ' block' + (count === 1 ? '' : 's') + ' rendered. Review and click \u201cUpdate\u201d to save.';
      });
    });

    $('body').on('click', '#lxp-ai-block-picker-btn', function (event) {
      event.preventDefault();
      $('#lxp-ai-block-picker-list').toggleClass('open');
    });

    $('body').on('click', '.lxp-block-picker-item', function (event) {
      event.preventDefault();
      tinyLxpInsertBlockMarker($(this).data('block-type'));
      $('#lxp-ai-block-picker-list').removeClass('open');
    });

    $(document).on('click', function (event) {
      if (!$(event.target).closest('.lxp-ai-block-picker-wrap').length) {
        $('#lxp-ai-block-picker-list').removeClass('open');
      }
    });

    // -----------------------------------------------------------------------
    // AI Content Gen — Reset button
    // -----------------------------------------------------------------------
    $('body').on('click', '#lxp-ai-content-reset-btn', function () {
      var postId = $('#lxp-ai-gen-post-id').val();
      if (!postId) {
        return;
      }

      if (!confirm('Restore the last pre-AI lesson content? Any unsaved AI-generated content will be replaced.')) {
        return;
      }

      tinyLxpSetAiButtonsDisabled(true);
      tinyLxpSetAiStatus('Restoring last pre-AI content\u2026', false);

      jQuery.ajax({
        type: 'get',
        dataType: 'json',
        url: window.location.origin + '/wp-json/lms/v1/lesson/original-content',
        data: { post_id: parseInt(postId, 10) },
        success: function (response) {
          var html = response && response.content ? response.content : '';
          tinyLxpSetEditorContent(html);
          tinyLxpSetAiStatus('Last pre-AI content restored. Click \u201cUpdate\u201d to save.', false);
        },
        error: function (xhr) {
          var msg = 'No pre-AI backup found.';
          try {
            var body = JSON.parse(xhr.responseText);
            if (body && body.message) {
              msg = body.message;
            }
          } catch (e) {}
          tinyLxpSetAiStatus(msg, true);
        },
        complete: function () {
          tinyLxpSetAiButtonsDisabled(false);
        }
      });
    });

    // ── AI Video Generation — 2-step workflow ─────────────────────────────

    // Open modal — load persisted content, route to Step 2 if script exists
    $('body').on('click', '#lxp-ai-video-open-modal-btn', function () {
      var postId = $('#lxp-ai-gen-post-id').val();
      $('#lxp-ai-video-modal-status').text('');
      $('#lxp-video-step1-status').text('');
      $('#lxp-ai-video-modal').show();
      lxpVideoGoToStep(1);

      if (!postId) {
        $('#lxp-ai-video-raw-text').val($('#lxp-ai-video-post-title').val() || '');
        return;
      }

      jQuery.ajax({
        type: 'get',
        dataType: 'json',
        url: (window.location.origin || '') + '/wp-json/lms/v1/lesson/ai-video-script',
        data: { post_id: parseInt(postId, 10) },
        success: function (response) {
          var rawText = response && response.raw_text ? response.raw_text : '';
          var script  = response && response.script  ? response.script  : '';
          var savedSecs = response && response.target_seconds ? response.target_seconds : 60;
          $('#lxp-ai-video-raw-text').val(rawText || $('#lxp-ai-video-post-title').val() || '');
          $('#lxp-ai-video-prompt').val(script);
          $('#lxp-video-duration').val(lxpFormatSecondsToMinSec(savedSecs));
          if (script) {
            lxpVideoGoToStep(2);
          }
        },
        error: function () {
          $('#lxp-ai-video-raw-text').val($('#lxp-ai-video-post-title').val() || '');
        }
      });
    });

    // Close modal via X button or overlay click
    $('body').on('click', '#lxp-ai-video-modal-close, .lxp-ai-video-modal-overlay', function (e) {
      if ($(e.target).is('#lxp-ai-video-modal-close') || $(e.target).is('.lxp-ai-video-modal-overlay')) {
        $('#lxp-ai-video-modal').hide();
      }
    });

    // Step 1 — Process with AI
    $('body').on('click', '#lxp-ai-video-script-btn', function () {
      var postId  = $('#lxp-ai-gen-post-id').val();
      var rawText = $('#lxp-ai-video-raw-text').val();

      if (!rawText.trim()) {
        lxpVideoSetStep1Status('Please paste your lesson content before processing.', true);
        return;
      }

      var sanitised = lxpSanitizeRawText(rawText);
      $('#lxp-ai-video-raw-text').val(sanitised);

      if (!sanitised.trim()) {
        lxpVideoSetStep1Status('No usable text found after cleaning. Please paste plain lesson content.', true);
        return;
      }

      var durationSecs = lxpParseDurationToSeconds($('#lxp-video-duration').val());
      if (durationSecs === null) {
        lxpVideoSetStep1Status('Invalid video length. Use M:SS format, e.g. 1:00. Minimum 0:30, maximum 5:00.', true);
        return;
      }

      tinyLxpSetAiButtonsDisabled(true);
      lxpVideoSetStep1Status('Converting lesson to structured scenes…', false);

      jQuery.ajax({
        type: 'post',
        dataType: 'json',
        url: (window.location.origin || '') + '/wp-json/lms/v1/lesson/ai-video-script',
        contentType: 'application/json',
        data: JSON.stringify({ post_id: parseInt(postId, 10), raw_text: sanitised, target_seconds: durationSecs }),
        success: function (response) {
          var script = response && response.script ? response.script : '';
          if (!script) {
            lxpVideoSetStep1Status('AI returned an empty script. Please try again.', true);
            tinyLxpSetAiButtonsDisabled(false);
            return;
          }
          $('#lxp-ai-video-prompt').val(script);
          lxpVideoGoToStep(2);
          tinyLxpSetAiButtonsDisabled(false);
        },
        error: function (xhr) {
          var msg = 'Processing failed.';
          try { var b = JSON.parse(xhr.responseText); if (b && b.message) { msg = b.message; } } catch (e) {}
          lxpVideoSetStep1Status(msg, true);
          tinyLxpSetAiButtonsDisabled(false);
        }
      });
    });

    // Step 1 — Restore Last Input
    $('body').on('click', '#lxp-video-restore-raw-btn', function () {
      var postId = $('#lxp-ai-gen-post-id').val();
      if (!postId) { return; }
      tinyLxpSetAiButtonsDisabled(true);
      jQuery.ajax({
        type: 'get', dataType: 'json',
        url: (window.location.origin || '') + '/wp-json/lms/v1/lesson/ai-video-script',
        data: { post_id: parseInt(postId, 10) },
        success: function (r) {
          if (r && r.raw_text) { $('#lxp-ai-video-raw-text').val(r.raw_text); lxpVideoSetStep1Status('Last input restored.', false); }
          else { lxpVideoSetStep1Status('No saved input found.', true); }
        },
        error: function () { lxpVideoSetStep1Status('Could not load saved input.', true); },
        complete: function () { tinyLxpSetAiButtonsDisabled(false); }
      });
    });

    // Step 2 — Back button
    $('body').on('click', '#lxp-video-back-btn', function () {
      lxpVideoGoToStep(1);
    });

    // Step 2 — Restore Last Script
    $('body').on('click', '#lxp-video-restore-script-btn', function () {
      var postId = $('#lxp-ai-gen-post-id').val();
      if (!postId) { return; }
      tinyLxpSetAiButtonsDisabled(true);
      jQuery.ajax({
        type: 'get', dataType: 'json',
        url: (window.location.origin || '') + '/wp-json/lms/v1/lesson/ai-video-script',
        data: { post_id: parseInt(postId, 10) },
        success: function (r) {
          if (r && r.script) { $('#lxp-ai-video-prompt').val(r.script); $('#lxp-ai-video-modal-status').text('Last script restored.'); }
          else { $('#lxp-ai-video-modal-status').text('No saved script found.'); }
        },
        error: function () { $('#lxp-ai-video-modal-status').text('Could not load saved script.'); },
        complete: function () { tinyLxpSetAiButtonsDisabled(false); }
      });
    });

    // Insert layout block marker into video prompt textarea (Step 2)
    $('body').on('click', '#lxp-video-insert-block-btn', function () {
      var slug = $('#lxp-video-layout-picker').val();
      if (slug) {
        lxpInsertVideoBlock(slug);
        $('#lxp-video-layout-picker').val('');
      }
    });

    // Generate video — call REST endpoint then start polling
    $('body').on('click', '#lxp-ai-video-generate-btn', function () {
      var postId = $('#lxp-ai-gen-post-id').val();
      var prompt = $('#lxp-ai-video-prompt').val().trim();
      if (!prompt) {
        $('#lxp-ai-video-modal-status').text('Please describe the lesson content.');
        return;
      }
      var genDurationSecs = lxpParseDurationToSeconds($('#lxp-video-duration').val());
      if (genDurationSecs === null) {
        $('#lxp-ai-video-modal-status').text('Invalid video length. Use M:SS format, e.g. 1:00. Minimum 0:30, maximum 5:00.');
        return;
      }
      tinyLxpSetAiButtonsDisabled(true);
      $('#lxp-ai-video-modal-status').text('Generating video script via AI…');
      jQuery.ajax({
        type: 'post',
        dataType: 'json',
        url: (window.location.origin || '') + '/wp-json/lms/v1/lesson/ai-video',
        contentType: 'application/json',
        data: JSON.stringify({ post_id: parseInt(postId, 10), prompt: prompt, target_seconds: genDurationSecs }),
        success: function (response) {
          if (response && response.render_id) {
            $('#lxp-ai-video-modal-status').text('Rendering video on AWS \u2014 this may take 60\u201390 seconds\u2026');
            lxpPollVideoStatus(postId, response.render_id);
          } else {
            tinyLxpSetAiButtonsDisabled(false);
            $('#lxp-ai-video-modal-status').text('Unexpected response from server. Please try again.');
          }
        },
        error: function (xhr) {
          var msg = 'Video generation failed.';
          try {
            var body = JSON.parse(xhr.responseText);
            if (body && body.message) { msg = body.message; }
          } catch (e) {}
          tinyLxpSetAiButtonsDisabled(false);
          $('#lxp-ai-video-modal-status').text(msg);
        }
      });
    });

    // Copy Video Link
    $('body').on('click', '.lxp-ai-video-copy-btn', function () {
      var url = $(this).data('video-url');
      tinyLxpCopyText(url, function (ok) {
        var el = document.getElementById('lxp-ai-video-action-status');
        if (!el) { return; }
        el.textContent = ok ? 'Link copied to clipboard.' : 'Copy failed \u2014 please copy the link manually.';
        el.className = 'lxp-ai-video-action-status ' + (ok ? 'lxp-ai-video-action-status-ok' : 'lxp-ai-video-action-status-error');
        setTimeout(function () { el.textContent = ''; el.className = 'lxp-ai-video-action-status'; }, 3000);
      });
    });

    // Insert Into Editor
    $('body').on('click', '.lxp-ai-video-insert-btn', function () {
      var url = $(this).data('video-url');
      var ok = lxpInsertVideoIntoEditor(url);
      var el = document.getElementById('lxp-ai-video-action-status');
      if (!el) { return; }
      el.textContent = ok ? 'Video embed inserted into editor.' : 'Could not insert \u2014 editor not available.';
      el.className = 'lxp-ai-video-action-status ' + (ok ? 'lxp-ai-video-action-status-ok' : 'lxp-ai-video-action-status-error');
      setTimeout(function () { el.textContent = ''; el.className = 'lxp-ai-video-action-status'; }, 3000);
    });

  });
})(jQuery);

// ---------------------------------------------------------------------------
// AI Content Gen helpers (defined in global scope so they are accessible
// from the jQuery IIFE and from tinyLxpSetEditorContent which uses tinymce).
// ---------------------------------------------------------------------------
function tinyLxpSetAiStatus(text, isError) {
  var el = document.getElementById('lxp-ai-content-status');
  if (!el) {
    return;
  }
  el.textContent = text;
  el.className = isError ? 'lxp-ai-status-error' : 'lxp-ai-status-ok';
}

function tinyLxpSetAiButtonsDisabled(isDisabled) {
  jQuery('#lxp-ai-content-gen-btn, #lxp-ai-blocks-gen-btn, #lxp-ai-block-picker-btn, #lxp-ai-content-reset-btn, #lxp-ai-video-open-modal-btn, #lxp-ai-video-generate-btn, #lxp-ai-video-script-btn, #lxp-video-restore-raw-btn, #lxp-video-restore-script-btn').prop('disabled', isDisabled);
}

function tinyLxpGetEditorContent() {
  var lessonContent = '';

  if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
    try {
      lessonContent = wp.data.select('core/editor').getEditedPostAttribute('content') || '';
    } catch (e) {
      lessonContent = '';
    }
  }
  if (!lessonContent && typeof tinymce !== 'undefined' && tinymce.get('content')) {
    lessonContent = tinymce.get('content').getContent();
  }
  if (!lessonContent) {
    lessonContent = jQuery('#content').val() || '';
  }

  return lessonContent;
}

function tinyLxpRunAiGeneration(endpoint, postId, lessonContent, progressMessage, getStatusMessage) {
  tinyLxpSetAiButtonsDisabled(true);
  tinyLxpSetAiStatus(progressMessage, false);

  jQuery.ajax({
    type: 'post',
    dataType: 'json',
    url: window.location.origin + endpoint,
    contentType: 'application/json',
    data: JSON.stringify({ post_id: parseInt(postId, 10), lesson_content: lessonContent }),
    success: function (response) {
      var html = response && response.content ? response.content : '';
      tinyLxpSetEditorContent(html);
      tinyLxpSetAiStatus(getStatusMessage(response), false);
    },
    error: function (xhr) {
      var msg = 'Generation failed.';
      try {
        var body = JSON.parse(xhr.responseText);
        if (body && body.message) {
          msg = body.message;
        }
      } catch (e) {}
      tinyLxpSetAiStatus(msg, true);
    },
    complete: function () {
      tinyLxpSetAiButtonsDisabled(false);
    }
  });
}

function tinyLxpInsertBlockMarker(type) {
  var marker = ':::' + type + '\n[your content here]\n:::';

  if (typeof wp !== 'undefined' && wp.blocks && wp.data && wp.data.dispatch('core/block-editor')) {
    try {
      wp.data.dispatch('core/block-editor').insertBlocks([
        wp.blocks.createBlock('core/paragraph', { content: marker.replace(/\n/g, '<br>') })
      ]);
      return;
    } catch (e) {}
  }

  if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
    tinymce.get('content').execCommand('mceInsertContent', false, marker.replace(/\n/g, '<br>'));
    return;
  }

  var ta = document.getElementById('content');
  if (ta) {
    var prefix = ta.value && !/\n$/.test(ta.value) ? '\n\n' : '';
    ta.value += prefix + marker + '\n';
  }
}

function tinyLxpSetEditorContent(html) {
  // Block editor (Gutenberg)
  if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
    try {
      wp.data.dispatch('core/editor').editPost({ content: html });
      return;
    } catch (e) {}
  }
  // Classic editor (TinyMCE)
  if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
    tinymce.get('content').setContent(html);
    return;
  }
  // Plain textarea fallback
  var ta = document.getElementById('content');
  if (ta) {
    ta.value = html;
  }
}

// ---------------------------------------------------------------------------
// Video render polling — called after a successful trigger_video_render POST.
// Polls every 5 s; updates modal status and the metabox status div on completion.
// ---------------------------------------------------------------------------
// ---------------------------------------------------------------------------
// Video action-area helpers
// ---------------------------------------------------------------------------

/**
 * Rebuild the metabox video action area with a play link, Copy Link button,
 * and Insert Into Editor button. Calling with an empty url clears the area.
 */
function lxpRenderVideoActionArea(url) {
  var el = document.getElementById('lxp-ai-video-status');
  if (!el) { return; }
  if (!url) {
    el.innerHTML = '<p id="lxp-ai-video-action-status" class="lxp-ai-video-action-status"></p>';
    return;
  }
  el.innerHTML =
    '<a href="' + url + '" target="_blank" rel="noopener" class="lxp-ai-video-link">&#9654; Play Last Generated Video</a>' +
    '<div class="lxp-ai-video-actions">' +
      '<button type="button" class="button lxp-ai-video-copy-btn" data-video-url="' + url + '">Copy Link</button>' +
      '<button type="button" class="button lxp-ai-video-insert-btn" data-video-url="' + url + '">Insert Into Editor</button>' +
    '</div>' +
    '<p id="lxp-ai-video-action-status" class="lxp-ai-video-action-status"></p>';
}

/**
 * Append an HTML5 video embed into the current lesson editor without
 * replacing the existing content.
 * Returns true when an editor was found and the content was inserted.
 */
function lxpInsertVideoIntoEditor(url) {
  var html =
    '<div class="lxp-lesson-video-embed" style="margin:16px 0;">' +
      '<video controls style="max-width:100%;display:block;">' +
        '<source src="' + url + '" type="video/mp4">' +
        '<a href="' + url + '" target="_blank" rel="noopener">Watch Video</a>' +
      '</video>' +
    '</div>';

  // Block editor (Gutenberg) — insert as HTML block, does not replace content
  if (typeof wp !== 'undefined' && wp.blocks && wp.data) {
    try {
      var dispatch = wp.data.dispatch('core/block-editor');
      if (dispatch && wp.blocks.createBlock) {
        dispatch.insertBlocks(wp.blocks.createBlock('core/html', { content: html }));
        return true;
      }
    } catch (e) {}
  }

  // Classic editor (TinyMCE) — append at cursor
  if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
    tinymce.get('content').execCommand('mceInsertContent', false, html);
    return true;
  }

  // Plain textarea fallback — append to end
  var ta = document.getElementById('content');
  if (ta) {
    ta.value += (ta.value.length > 0 && ta.value.slice(-1) !== '\n' ? '\n' : '') + html;
    return true;
  }

  return false;
}

function lxpInsertVideoBlock(slug) {
  var ta = document.getElementById('lxp-ai-video-prompt');
  if (!ta) { return; }
  var start  = ta.selectionStart;
  var end    = ta.selectionEnd;
  var before = ta.value.substring(0, start);
  var after  = ta.value.substring(end);
  var prefix = (before.length > 0 && !/\n$/.test(before)) ? '\n\n' : '';
  var block  = prefix + ':::' + slug + '\n\n:::';
  ta.value   = before + block + '\n' + after;
  // Position cursor on the blank line inside the block (after :::slug\n)
  var cursorPos = start + prefix.length + 4 + slug.length;
  ta.setSelectionRange(cursorPos, cursorPos);
  ta.focus();
}

// ---------------------------------------------------------------------------
// Video duration helpers  (M:SS format)
// ---------------------------------------------------------------------------

/**
 * Parse a "M:SS" or "MM:SS" string to integer seconds.
 * Returns null if the format is invalid or out of the 0:30–5:00 range.
 */
function lxpParseDurationToSeconds(val) {
  if (!val || !/^\d{1,2}:[0-5]\d$/.test(val.trim())) { return null; }
  var parts = val.trim().split(':');
  var secs  = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
  if (secs < 30 || secs > 300) { return null; }
  return secs;
}

/**
 * Format integer seconds to "M:SS" string (e.g. 90 → "1:30").
 */
function lxpFormatSecondsToMinSec(seconds) {
  var m = Math.floor(seconds / 60);
  var s = seconds % 60;
  return m + ':' + (s < 10 ? '0' : '') + s;
}

// ---------------------------------------------------------------------------
// 2-step video wizard helpers
// ---------------------------------------------------------------------------

/**
 * Navigate between wizard steps.
 * @param {number} step  1 or 2
 */
function lxpVideoGoToStep(step) {
  var isStep1 = step === 1;
  jQuery('#lxp-video-step-1').toggle(isStep1);
  jQuery('#lxp-video-step-2').toggle(!isStep1);
  jQuery('#lxp-vws-1').toggleClass('active', isStep1);
  jQuery('#lxp-vws-2').toggleClass('active', !isStep1);
}

/**
 * Set Step 1 status message.
 * @param {string}  text
 * @param {boolean} isError
 */
function lxpVideoSetStep1Status(text, isError) {
  var el = document.getElementById('lxp-video-step1-status');
  if (!el) { return; }
  el.textContent = text;
  el.className = 'lxp-video-step-status ' + (isError ? 'lxp-video-step-status-error' : 'lxp-video-step-status-ok');
}

/**
 * Client-side sanitisation of raw lesson text before sending to the server.
 * The server performs the authoritative sanitisation; this is UX feedback only.
 * @param {string} text
 * @returns {string}
 */
function lxpSanitizeRawText(text) {
  // Strip HTML tags
  text = text.replace(/<[^>]*>/g, '');
  // Strip markdown headers (##, ###, …)
  text = text.replace(/^#{1,6}\s*/gm, '');
  // Strip markdown bold/italic (**text**, __text__, *text*, _text_)
  text = text.replace(/(\*\*|__)(.+?)\1/gs, '$2');
  text = text.replace(/(\*|_)(.+?)\1/gs, '$2');
  // Strip markdown links [label](url) → label
  text = text.replace(/\[([^\]]+)\]\([^)]+\)/g, '$1');
  // Normalise Unicode and ASCII bullet chars at line start → '- '
  text = text.replace(/^[•‣◦⁃∙\*]\s*/gm, '- ');
  // Remove horizontal rules
  text = text.replace(/^(-{3,}|\*{3,}|_{3,})\s*$/gm, '');
  // Collapse 3+ blank lines to 2
  text = text.replace(/\n{3,}/g, '\n\n');
  return text.trim();
}

function lxpPollVideoStatus(postId, renderId) { // renderId kept for future use
  var pollInterval = setInterval(function () {
    jQuery.ajax({
      type: 'get',
      dataType: 'json',
      url: (window.location.origin || '') + '/wp-json/lms/v1/lesson/ai-video',
      data: { post_id: parseInt(postId, 10) },
      success: function (response) {
        if (response.status === 'done') {
          clearInterval(pollInterval);
          tinyLxpSetAiButtonsDisabled(false);
          var url = response.video_url || '';
          lxpRenderVideoActionArea(url);
          jQuery('#lxp-ai-video-modal-status').text('Video ready!');
          setTimeout(function () { jQuery('#lxp-ai-video-modal').hide(); }, 1800);
        } else if (response.status === 'error') {
          clearInterval(pollInterval);
          tinyLxpSetAiButtonsDisabled(false);
          jQuery('#lxp-ai-video-modal-status').text('Render failed on AWS Lambda. Please try again.');
        } else {
          var pct = response.progress ? Math.round(response.progress * 100) : 0;
          jQuery('#lxp-ai-video-modal-status').text('Rendering\u2026 ' + pct + '%');
        }
      },
      error: function () {
        clearInterval(pollInterval);
        tinyLxpSetAiButtonsDisabled(false);
        jQuery('#lxp-ai-video-modal-status').text('Error checking render status. Please refresh and check again.');
      }
    });
  }, 5000);
}
