import React from 'react';
import ServerDetail from './ServerDetail.jsx';

export default class Servers extends React.Component {

    static propTypes = {

    };

    render() {
        const {data, data: {dbStats, serverStatus, hostInfo, buildInfo, init, log}} = this.props;

        return (
            <div className="panel-group" id="accordion" role="tablist" aria-multiselectable="false">
                {Object.keys(data.init).map((hostId, serverIndex) => {
                    const server = data.init[hostId];
                    
                    return (
                        <ServerDetail
                            key={`server-detail-${serverIndex}`}
                            url={server.url}
                            hostId={server.hostId}
                            init={init[server.hostId]}
                            dbStats={dbStats[server.hostId]}
                            serverStats={serverStatus[server.hostId]}
                            hostInfo={hostInfo[server.hostId]}
                            buildInfo={buildInfo[server.hostId]}
                            log={log[server.hostId]}
                            index={serverIndex}
                        />
                    )
                })}
            </div>
        )
    }

}