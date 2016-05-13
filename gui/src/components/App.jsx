import React from 'react';
/**
 * Import main SCSS file
 */
import '../style/main.scss';

/**
 * Import display components
 */
import Servers from './Servers.jsx';


export default class App extends React.Component {
    
    constructor(props) {
        super(props);
        
        this.state = App.getInitialState();
        this.socket = null;
    }

    /**
     * Static initial defaultstate o App component
     * - used for resetting data from servers
     *
     * @returns {{errors: Array, data: {init: Array, hostInfo: Array, serverStatus: Array, top: Array, dbStats: Array, buildInfo: Array}, connected: boolean}}
     */
    static getInitialState() {
        return {
            error: null,
            data: {
                init: [],
                hostInfo: [],
                serverStatus: [],
                top: [],
                dbStats: [],
                buildInfo: [],
                log: []
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
                {this.state.error ? <div className="alert alert-danger" role="alert"><p>{this.state.error}</p></div> : null}
                {this.renderControlButtons()}
                {this.state.data.init.length ? <Servers data={this.state.data} /> : null}
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
        this.socket.onclose = () => {};
        this.socket.close();
        
        this.setState({
            connected: false
        });
    }
    
    initWebsocket() {

        this.socket = new WebSocket(`ws://${this.props.websocketUrl}/echo`);
        this.setState(App.getInitialState());

        this.socket.onopen = () => {
            this.setState({
                connected: true
            });
        };

        this.socket.onmessage = (evt) => {
            try {
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
            } catch(e) {
                // Empty data from server
            }

        };

        this.socket.onerror = (event) => {
            let reason = '';

            if (event.code == 1000)
                reason = "Normal closure, meaning that the purpose for which the connection was established has been fulfilled.";
            else if(event.code == 1001)
                reason = "An endpoint is \"going away\", such as a server going down or a browser having navigated away from a page.";
            else if(event.code == 1002)
                reason = "An endpoint is terminating the connection due to a protocol error";
            else if(event.code == 1003)
                reason = "An endpoint is terminating the connection because it has received a type of data it cannot accept (e.g., an endpoint that understands only text data MAY send this if it receives a binary message).";
            else if(event.code == 1004)
                reason = "Reserved. The specific meaning might be defined in the future.";
            else if(event.code == 1005)
                reason = "No status code was actually present.";
            else if(event.code == 1006)
                reason = "The connection was closed abnormally, e.g., without sending or receiving a Close control frame";
            else if(event.code == 1007)
                reason = "An endpoint is terminating the connection because it has received data within a message that was not consistent with the type of the message (e.g., non-UTF-8 [http://tools.ietf.org/html/rfc3629] data within a text message).";
            else if(event.code == 1008)
                reason = "An endpoint is terminating the connection because it has received a message that \"violates its policy\". This reason is given either if there is no other sutible reason, or if there is a need to hide specific details about the policy.";
            else if(event.code == 1009)
                reason = "An endpoint is terminating the connection because it has received a message that is too big for it to process.";
            else if(event.code == 1010) // Note that this status code is not used by the server, because it can fail the WebSocket handshake instead.
                reason = "An endpoint (client) is terminating the connection because it has expected the server to negotiate one or more extension, but the server didn't return them in the response message of the WebSocket handshake. <br /> Specifically, the extensions that are needed are: " + event.reason;
            else if(event.code == 1011)
                reason = "A server is terminating the connection because it encountered an unexpected condition that prevented it from fulfilling the request.";
            else if(event.code == 1015)
                reason = "The connection was closed due to a failure to perform a TLS handshake (e.g., the server certificate can't be verified).";
            else
                reason = "Unknown error in websocket or invalid URL address";

            this.setState({
                error: reason
            });
        };

        this.socket.onclose = (evt) => {
            this.stopWebsocket();
        };
    }
}