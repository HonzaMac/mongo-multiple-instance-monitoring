import React from 'react';
import Classname from 'classname';

import JsonDetail from './JsonDetail.jsx';

export default class ServerDetail extends React.Component {

    render() {
        const {hostInfo, buildInfo, init, index} = this.props;

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
                    <div id={`panel-collapse-${index}`} className="panel-collapse collapse in"
                         aria-expanded={index === 0}>
                        <div className="panel-body">

                            <h4>Host info <JsonDetail title="Host info" code={JSON.stringify(hostInfo.length && hostInfo[0].data)} /></h4>
                            {this.renderHostInfo(hostInfo.length && hostInfo[0].data)}

                            <h4>Build info <JsonDetail title="Build info" code={JSON.stringify(buildInfo.length && buildInfo[0].data)} /></h4>
                            {this.renderBuildInfo(buildInfo.length && buildInfo[0].data)}

                            <h4>Databases <JsonDetail title="Databases" code={JSON.stringify(init.length && init[0].listDBs.databases)} /></h4>
                            {this.renderDatabases(init.length && init[0].listDBs.databases)}
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

    renderDatabases(data) {
        return (
            <ul className="list-group">
                {data.sort((a, b) => a.name > b.name).map((db) => {
                    return (
                        <li className="list-group-item">
                            {db.name}
                        </li>
                    )
                })}
            </ul>
        )
    }
}