import "babel-polyfill";
import React from "react";
import {render} from "react-dom";
import App from "./components/App.jsx";

/**
 * If websocket is not supported do not start an application
 */
if (!"WebSocket" in window) {
  alert("WebSocket NOT supported by your Browser!");

} else if (document.getElementById('app')) {
  /**
   * Render JS application into html element
   */
  render(
    <App websocketUrl="localhost:9900"/>,
    document.getElementById('app')
  );
}
