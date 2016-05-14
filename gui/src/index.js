import 'babel-polyfill';
import React from 'react';
import {render} from 'react-dom';
import App from './components/App.jsx';

/**
 * If websocket is not supported do not start an application
 */
if (! "WebSocket" in window) {
	alert("WebSocket NOT supported by your Browser!");

} else if(document.getElementById('app')) {

	const query = window.location.search;
	const host = /host=([a-z0-9-_\.@:]+)/ig.exec(query);
	const port = /port=([\d]+)/g.exec(query);

	const url = host && port ? host[1] + ':' + port[1] : 'localhost:9900';

	/**
	 * Render JS application into html element
	 */
	render(
		<App websocketUrl={url} />,
		document.getElementById('app')
	);
} else {
	console.error('Missing placeholder element with id "app" for application!');
}
