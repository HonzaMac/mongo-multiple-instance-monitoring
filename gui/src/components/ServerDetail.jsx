import React from 'react';
import Classname from 'classname';
import Humanize from 'humanize';

import JsonDetail from './JsonDetail.jsx';

export default class ServerDetail extends React.Component {

    render() {
        const {hostInfo, buildInfo, init, index, log, dbStats} = this.props;

        return (
            <div className={Classname('col-md-4 col-xs-12', {clearfix: index % 3 === 0})}>
                <div className="panel panel-primary" id={`panel-${index}`}>
                    <div className="panel-heading" role="tab">
                        <h3 className="panel-title">
                            <a
                                role="button"
                                data-toggle="collapse"
                                href={`#panel-collapse-${index}`}
                                aria-controls={`panel-collapse-${index}`}
                                aria-expanded={index === 0}>
                                {this.props.url}
                            </a>
                        </h3>
                    </div>
                    <div id={`panel-collapse-${index}`} className="panel-collapse collapse in" aria-expanded={index === 0}>
                        <div className="panel-body">

                            <h4>Host info <JsonDetail title="Host info" code={JSON.stringify(hostInfo.length && hostInfo[hostInfo.length - 1].data)} /></h4>
                            {this.renderHostInfo(hostInfo.length && hostInfo[hostInfo.length - 1].data)}

                            <h4>Build info <JsonDetail title="Build info" code={JSON.stringify(buildInfo.length && buildInfo[buildInfo.length - 1].data)} /></h4>
                            {this.renderBuildInfo(buildInfo.length && buildInfo[buildInfo.length - 1].data)}

                            <h4>Databases <JsonDetail title="Databases" code={JSON.stringify(init.length && init[init.length - 1].listDBs.databases)} /></h4>
                            {this.renderDatabases(init.length && init[init.length - 1].listDBs.databases, dbStats.length && dbStats)}

                            <h4>Logs</h4>
                            {this.renderLogMessages(log && log.length && log[log.length - 1].data)}
                        </div>
                    </div>
                </div>
            </div>
        )
    }

    renderHostInfo(data) {
        if (!data) {
            return null;
        }
        return (
            <table className="table table-bordered">
                <tbody>
                <tr>
                    <td>versionSignature</td>
                    {data.os ? <td>{data.os.name} {data.os.version} {data.os.type}</td> : <td></td>}
                </tr>
                <tr>
                    <td>hostname</td>
                    {data.system ? <td>{data.system.hostname}</td> : <td></td>}
                </tr>
                <tr>
                    <td>memSizeMB</td>
                    {data.system ? <td>{data.system.memSizeMB}</td> : <td></td>}
                </tr>
                </tbody>
            </table>
        )
    }

    renderBuildInfo(data) {
        return (
            <table className="table table-bordered">
                <tbody>
                <tr>
                    <td>version</td>
                    <td>{data.version}</td>
                </tr>
                </tbody>
            </table>
        )
    }

    renderDatabases(data, stats) {

        const renderStats = (stats) => {
          return (
              <p>
                  Collections <span className="badge pull-right">{stats.collections}</span><br />
                  Indexes <span className="badge pull-right">{stats.indexes}</span><br />
                  Index sizes <span className="badge pull-right">{Humanize.filesize(stats.indexSize)}</span><br />
                  File size <span className="badge pull-right">{Humanize.filesize(stats.fileSize)}</span>
              </p>
          )
        };

        return (
            <ul className="list-group">
                {data.sort((a, b) => a.name > b.name).map((db, dbIndex) => {
                    const dbStats = stats && stats.find((s) => s.data.db == db.name);

                    return (
                        <li key={`detail-db-${dbIndex}`} className="list-group-item">
                            <strong>{db.name}</strong>
                            {dbStats ? renderStats(dbStats.data) : null}
                        </li>
                    )
                })}
            </ul>
        )
    }

    renderLogMessages(data) {
        if (data) {
            return (
                <div style={{color: '#0c0', backgroundColor: '#222', maxHeight: '400px', overflow: 'scroll'}}>
                    {Object.keys(data).map((rowNumber) => Number(rowNumber)).reverse().map((key, rowKey) => {
                        return <p key={`log-row-${rowKey}`}>{data[key]}</p>
                    })}
                </div>
            )
        } else {
            return <p>Waiting for logs...</p>
        }
    }
}