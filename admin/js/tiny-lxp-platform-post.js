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

      // Read lesson content from whichever editor is active.
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
        lessonContent = $('#content').val() || '';
      }

      if (!lessonContent.trim()) {
        tinyLxpSetAiStatus('Please add lesson content before generating.', true);
        return;
      }

      $('#lxp-ai-content-gen-btn, #lxp-ai-content-reset-btn').prop('disabled', true);
      tinyLxpSetAiStatus('Generating\u2026 this may take 15\u201330 seconds.', false);

      jQuery.ajax({
        type: 'post',
        dataType: 'json',
        url: window.location.origin + '/wp-json/lms/v1/lesson/ai-content',
        contentType: 'application/json',
        data: JSON.stringify({ post_id: parseInt(postId, 10), lesson_content: lessonContent }),
        success: function (response) {
          var html = response && response.content ? response.content : '';
          tinyLxpSetEditorContent(html);
          var statusMsg = 'Content generated. Review and click \u201cUpdate\u201d to save.';
          if (response && response.template_id) {
            statusMsg += ' (Template ' + response.template_id + ' applied)';
          }
          tinyLxpSetAiStatus(statusMsg, false);
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
          $('#lxp-ai-content-gen-btn, #lxp-ai-content-reset-btn').prop('disabled', false);
        }
      });
    });

    // -----------------------------------------------------------------------
    // AI Content Gen — Reset button
    // -----------------------------------------------------------------------
    $('body').on('click', '#lxp-ai-content-reset-btn', function () {
      var postId = $('#lxp-ai-gen-post-id').val();
      if (!postId) {
        return;
      }

      if (!confirm('Restore the original lesson content? Any unsaved AI-generated content will be replaced.')) {
        return;
      }

      $('#lxp-ai-content-gen-btn, #lxp-ai-content-reset-btn').prop('disabled', true);
      tinyLxpSetAiStatus('Restoring original content\u2026', false);

      jQuery.ajax({
        type: 'get',
        dataType: 'json',
        url: window.location.origin + '/wp-json/lms/v1/lesson/original-content',
        data: { post_id: parseInt(postId, 10) },
        success: function (response) {
          var html = response && response.content ? response.content : '';
          tinyLxpSetEditorContent(html);
          tinyLxpSetAiStatus('Original content restored. Click \u201cUpdate\u201d to save.', false);
        },
        error: function (xhr) {
          var msg = 'No original backup found.';
          try {
            var body = JSON.parse(xhr.responseText);
            if (body && body.message) {
              msg = body.message;
            }
          } catch (e) {}
          tinyLxpSetAiStatus(msg, true);
        },
        complete: function () {
          $('#lxp-ai-content-gen-btn, #lxp-ai-content-reset-btn').prop('disabled', false);
        }
      });
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
