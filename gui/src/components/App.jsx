import React from "react";
import "../style/main.scss";
import Errors from "./Errors.jsx";
import Servers from "./Servers.jsx";
/**
 * Import main SCSS file
 */

/**
 * Import display components
 */


export default class App extends React.Component {

  constructor(props) {
    super(props);

    this.state = App.getInitialState();
  }

  /**
   * Static initial defaultstate o App component
   * - used for resetting data from servers
   *
   * @returns {{errors: Array, data: {init: Array, hostInfo: Array, serverStatus: Array, top: Array, dbStats: Array, buildInfo: Array}, connected: boolean}}
   */
  static getInitialState() {
    return {
      errors: [],
      data: {
        init: [],
        hostInfo: [],
        serverStatus: [],
        top: [],
        dbStats: [],
        buildInfo: []
      },
      connected: false
    };
  }

  componentDidMount() {
    /**
     * When HTML element is rendered into DOM init websocket
     */
    this.initWebsocket();
  }

  render() {
    return (
      <div className="container">
        <Errors data={this.state.errors}/>
        {this.renderControlButtons()}
        {this.state.data.init.length ? <Servers data={this.state.data}/> : null}
      </div>
    )
  }

  renderControlButtons() {
    if (this.state.connected) {
      return (
        <div className="row">
          <p>
            <button onClick={() => this.stopWebsocket()} className="btn btn-danger">STOP socket</button>
          </p>
        </div>
      )
    } else {
      return (
        <div className="row">
          <p>Connection is closed.</p>
          <p>
            <button onClick={() => this.initWebsocket()} className="btn btn-primary">Click to reconnect</button>
          </p>
        </div>
      )
    }
  }

  stopWebsocket() {
    /**
     * Correctly closing socket
     */
    this.socket.onclose = () => {
    };
    this.socket.close();

    this.setState({
      connected: false
    });
  }

  initWebsocket() {

    this.socket = new WebSocket(`ws://${this.props.websocketUrl}/echo`);
    this.setState(App.getInitialState());

    this.socket.onopen = () => {
      // Web Socket is connected, send data using send()
      this.setState({
        connected: true
      });
    };

    this.socket.onmessage = (evt) => {
      const message = JSON.parse(evt.data);
      const {type} = message;

      if (typeof this.state.data[type] !== 'undefined') {
        let values = this.state.data[type];
        values.push(message);

        this.setState({
          ...this.state.data,
          [type]: values
        })
      }

    };

    this.socket.onerror = (evt) => {
      console.error(evt);

      let errors = this.state.errors;
      this.setState({
        errors: errors.push('Error in Websocket occured. See console log for more info')
      });
    };

    this.socket.onclose = (evt) => {
      console.log('closed', evt);

      this.setState({
        connected: false
      });
    };
  }
}