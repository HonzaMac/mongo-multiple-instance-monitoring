import React from 'react';
import ServerDetail from './ServerDetail.jsx';

export default class Servers extends React.Component {

    static propTypes = {

    };

    render() {
        const {data, data: {dbStats, serverStatus, hostInfo, buildInfo, init, log}} = this.props;

        return (
            <div className="panel-group" id="accordion" role="tablist" aria-multiselectable="false">
                {data.init.map((server, serverIndex) => {
                    return (
                        <ServerDetail
                            url={server.url}
                            hostId={server.hostId}
                            init={init.filter(i => i.hostId === server.hostId)}
                            dbStats={dbStats.filter(i => i.hostId === server.hostId)}
                            serverStats={serverStatus.filter(i => i.hostId === server.hostId)}
                            hostInfo={hostInfo.filter(i => i.hostId === server.hostId)}
                            buildInfo={buildInfo.filter(i => i.hostId === server.hostId)}
                            log={log.filter(i => i.hostId === server.hostId)}
                            index={serverIndex}
                        />
                    )
                })}
            </div>
        )
    }

}