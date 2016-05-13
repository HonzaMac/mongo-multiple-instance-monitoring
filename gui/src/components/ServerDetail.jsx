import React from 'react';
import Classname from 'classname';

import JsonDetail from './JsonDetail.jsx';

export default class ServerDetail extends React.Component {

    render() {
        const {hostInfo, buildInfo, init, index, log, dbStats} = this.props;

        return (
            <div className={Classname('col-md-6 col-xs-12', {clearfix: index % 2 === 0})}>
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
                            {this.renderDatabases(init.length && init[init.length - 1].listDBs.databases, dbStats.length && dbStats[dbStats.length - 1].data)}

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
                    <td>{data.extra.versionSignature}</td>
                </tr>
                <tr>
                    <td>hostname</td>
                    <td>{data.system.hostname}</td>
                </tr>
                <tr>
                    <td>memSizeMB</td>
                    <td>{data.system.memSizeMB}</td>
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
                  Collections <span className="badge">{stats.collections}</span><br />
                  Indexes <span className="badge">{stats.indexes}</span><br />
                  Index sizes <span className="badge">{stats.indexSize}</span><br />
                  File size <span className="badge">{stats.fileSize}</span>
              </p>
          )
        };

        return (
            <ul className="list-group">
                {data.sort((a, b) => a.name > b.name).map((db) => {
                    return (
                        <li className="list-group-item">
                            <strong>{db.name}</strong>
                            {stats && stats.db == db.name ? renderStats(stats) : null}
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
                    {Object.keys(data).map((rowNumber) => Number(rowNumber)).reverse().map((key) => {
                        return <p>{data[key]}</p>
                    })}
                </div>
            )
        } else {
            return <p>Waiting for logs...</p>
        }
    }
}