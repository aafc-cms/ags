(function (Drupal) {
    Drupal.behaviors.chatToggle = {
      attach: function (context, settings) {
        if (window.chatToggleRegistered) {
          return;
        }
        window.chatToggleRegistered = true;

        const iframe = context.querySelector("#chatbot-iframe");
        if (!iframe) return;

        function setClosed() {
          iframe.style.position = "fixed";
          iframe.style.top = "auto";
          iframe.style.left = "auto";
          iframe.style.bottom = "0";
          iframe.style.right = "0";
          iframe.style.width = "144px";
          iframe.style.height = "144px";
          iframe.style.border = "none";
          iframe.style.background = "transparent";
          iframe.style.zIndex = "9999";
        }
  
        function setOpen() {
          iframe.style.position = "fixed";
          iframe.style.top = "0";
          iframe.style.right = "0";
          iframe.style.bottom = "0";
          iframe.style.left = "0";
          iframe.style.width = "100vw";
          iframe.style.height = "100vh";
          iframe.style.border = "none";
          iframe.style.background = "transparent";
          iframe.style.zIndex = "9999";
        }
  
        setClosed();
  
        window.addEventListener("message", function (event) {
          if (!event.data || event.data.type !== "CHAT_TOGGLE") return;
          if (event.data.state === "open") {
            setOpen();
          } else {
            setClosed();
          }
        });
      }
    };
  })(Drupal);
  