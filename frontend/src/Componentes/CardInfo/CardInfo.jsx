function CardInfo(props){

    const temStatus = props.status && props.status !== ""
    const temIcone = props.icon && props.icon !== ""
    const iconeTrofeu = props.icon && props.icon === "/icons/trophy-icon.svg"
    const iconePessoa = props.icon && props.icon === "/icons/group-icon.svg"
    return(
        <div className={`${props.status === "Finalizado" ? 'bg-gray-200' : ''} w-full shadow-md p-5 flex justify-between items-center mb-3`}>
            {temIcone && (
                <div>
                    <img className={`${iconeTrofeu ? 'invert' : ''} ${iconePessoa ? 'w-8' : ''}`} src={props.icon} alt={props.alt} />
                </div>
            )}
            <div className="flex flex-col justify-center">
                <h2 className="text-xl font-bold">{props.titulo}</h2>
                {temStatus && (
                    <p className="text-sm text-gray-400">({props.status})</p>
                )}
            </div>
            <div className="flex items-center justify-center">
                <img src="/icons/arrow-icon.svg" alt="Icone Seta" />
            </div>
        </div>
    )
}

export default CardInfo